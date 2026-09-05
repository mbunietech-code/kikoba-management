import 'package:flutter_test/flutter_test.dart';

import 'package:benja_kikoba/data/selectors.dart';

void main() {
  test('group summary computes', () {
    final s = groupSummary();
    expect(s.totalMembers, greaterThan(0));
    expect(s.netProfit, isA<int>());
  });
}
