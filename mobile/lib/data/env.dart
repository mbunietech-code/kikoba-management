/// Backend configuration.
///
/// Override at build/run time without editing code:
///   flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
///
/// Defaults to the live server.
class Env {
  static const apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://kikoba.mt.co.tz/api/v1',
  );
}
