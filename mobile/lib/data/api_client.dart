import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import 'env.dart';

/// Thrown for any non-2xx response or transport failure.
class ApiException implements Exception {
  ApiException(this.message, {this.status, this.code, this.errors});

  final String message;
  final int? status;
  final String? code;
  final Map<String, dynamic>? errors;

  bool get isAuth => status == 401;
  bool get isForbidden => status == 403;

  @override
  String toString() => message;
}

/// Low-level HTTP layer: base URL, bearer token, `{success,data,message}`
/// envelope unwrapping, and a single automatic token refresh on 401.
class ApiClient {
  ApiClient._();
  static final ApiClient instance = ApiClient._();

  final _http = http.Client();

  static const _kAccess = 'benja.access_token';
  static const _kRefresh = 'benja.refresh_token';

  String? _access;
  String? _refresh;
  bool _loaded = false;
  Future<bool>? _refreshing;

  Future<void> _ensureLoaded() async {
    if (_loaded) return;
    final p = await SharedPreferences.getInstance();
    _access = p.getString(_kAccess);
    _refresh = p.getString(_kRefresh);
    _loaded = true;
  }

  bool get hasSession => _access != null;

  Future<void> setTokens({required String access, required String refresh}) async {
    _access = access;
    _refresh = refresh;
    _loaded = true;
    final p = await SharedPreferences.getInstance();
    await p.setString(_kAccess, access);
    await p.setString(_kRefresh, refresh);
  }

  Future<void> clear() async {
    _access = null;
    _refresh = null;
    final p = await SharedPreferences.getInstance();
    await p.remove(_kAccess);
    await p.remove(_kRefresh);
  }

  Uri _uri(String path, [Map<String, dynamic>? query]) {
    final base = Env.apiBaseUrl.endsWith('/')
        ? Env.apiBaseUrl.substring(0, Env.apiBaseUrl.length - 1)
        : Env.apiBaseUrl;
    final p = path.startsWith('/') ? path : '/$path';
    final q = query?.map((k, v) => MapEntry(k, '$v'));
    return Uri.parse('$base$p').replace(queryParameters: q?.isEmpty ?? true ? null : q);
  }

  Map<String, String> _headers({bool auth = true, bool json = true}) => {
        'Accept': 'application/json',
        if (json) 'Content-Type': 'application/json',
        if (auth && _access != null) 'Authorization': 'Bearer $_access',
      };

  Future<dynamic> get(String path, {Map<String, dynamic>? query, bool auth = true}) =>
      _send('GET', path, query: query, auth: auth);

  /// Fetches every page of a paginated list endpoint and returns the merged
  /// items. Capped at [maxPages] to stay safe on very large collections.
  Future<List<dynamic>> getAll(String path,
      {Map<String, dynamic>? query, int perPage = 100, int maxPages = 20}) async {
    final out = <dynamic>[];
    var page = 1;
    while (page <= maxPages) {
      final res = await _send('GET', path,
          query: {...?query, 'per_page': perPage, 'page': page}, raw: true);
      if (res is! Map) break;
      final data = res['data'];
      if (data is List) out.addAll(data);
      final meta = res['meta'];
      final last = meta is Map ? (meta['last_page'] as num?)?.toInt() ?? page : page;
      if (page >= last) break;
      page++;
    }
    return out;
  }

  Future<dynamic> post(String path, {Object? body, bool auth = true}) =>
      _send('POST', path, body: body, auth: auth);

  Future<dynamic> put(String path, {Object? body, bool auth = true}) =>
      _send('PUT', path, body: body, auth: auth);

  Future<dynamic> patch(String path, {Object? body, bool auth = true}) =>
      _send('PATCH', path, body: body, auth: auth);

  Future<dynamic> delete(String path, {Object? body, bool auth = true}) =>
      _send('DELETE', path, body: body, auth: auth);

  Future<dynamic> _send(
    String method,
    String path, {
    Map<String, dynamic>? query,
    Object? body,
    bool auth = true,
    bool isRetry = false,
    bool raw = false,
  }) async {
    await _ensureLoaded();

    http.Response res;
    try {
      final req = http.Request(method, _uri(path, query))
        ..headers.addAll(_headers(auth: auth));
      if (body != null) req.body = jsonEncode(body);
      final streamed = await _http.send(req).timeout(const Duration(seconds: 25));
      res = await http.Response.fromStream(streamed);
    } on TimeoutException {
      throw ApiException('The server took too long to respond. Check your connection.');
    } catch (e) {
      throw ApiException('Could not reach the server. Check your connection.');
    }

    if (res.statusCode == 401 && auth && !isRetry && _refresh != null) {
      final ok = await _tryRefresh();
      if (ok) {
        return _send(method, path, query: query, body: body, auth: auth, isRetry: true, raw: raw);
      }
    }

    dynamic decoded;
    if (res.body.isNotEmpty) {
      try {
        decoded = jsonDecode(res.body);
      } catch (_) {
        decoded = null;
      }
    }

    if (res.statusCode >= 200 && res.statusCode < 300) {
      if (raw) return decoded;
      if (decoded is Map && decoded['success'] == true) {
        return decoded.containsKey('data') ? decoded['data'] : decoded['message'];
      }
      return decoded;
    }

    // error envelope: {success:false, error:{code,message}} or Laravel validation
    String message = 'Something went wrong (${res.statusCode}).';
    String? code;
    Map<String, dynamic>? errors;
    if (decoded is Map) {
      if (decoded['error'] is Map) {
        message = decoded['error']['message']?.toString() ?? message;
        code = decoded['error']['code']?.toString();
      } else if (decoded['message'] != null) {
        message = decoded['message'].toString();
      }
      if (decoded['errors'] is Map) {
        errors = Map<String, dynamic>.from(decoded['errors']);
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) message = first.first.toString();
      }
    }
    throw ApiException(message, status: res.statusCode, code: code, errors: errors);
  }

  Future<bool> _tryRefresh() {
    return _refreshing ??= () async {
      try {
        final res = await _http
            .post(_uri('/auth/refresh'),
                headers: _headers(auth: false),
                body: jsonEncode({'refresh_token': _refresh}))
            .timeout(const Duration(seconds: 20));
        if (res.statusCode != 200) return false;
        final data = jsonDecode(res.body);
        final d = data is Map ? data['data'] : null;
        if (d is Map && d['access_token'] != null) {
          await setTokens(
            access: d['access_token'],
            refresh: d['refresh_token'] ?? _refresh!,
          );
          return true;
        }
        return false;
      } catch (_) {
        return false;
      } finally {
        _refreshing = null;
      }
    }();
  }
}
