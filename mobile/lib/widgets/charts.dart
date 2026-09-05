import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import '../data/format.dart';
import '../theme/tokens.dart';

class AreaTrend extends StatelessWidget {
  const AreaTrend({super.key, required this.data, required this.keys, required this.colors, this.height = 200});
  final List<Map<String, dynamic>> data;
  final List<String> keys;
  final List<Color> colors;
  final double height;

  @override
  Widget build(BuildContext context) {
    if (data.isEmpty) return SizedBox(height: height);
    double maxY = 0;
    for (final row in data) {
      for (final k in keys) {
        final v = (row[k] as num).toDouble();
        if (v > maxY) maxY = v;
      }
    }
    maxY = maxY == 0 ? 1 : maxY * 1.15;

    return SizedBox(
      height: height,
      child: LineChart(
        LineChartData(
          minY: 0,
          maxY: maxY,
          gridData: FlGridData(show: true, drawVerticalLine: false, horizontalInterval: maxY / 4,
              getDrawingHorizontalLine: (_) => const FlLine(color: K.neutral200, strokeWidth: 1, dashArray: [4, 4])),
          titlesData: FlTitlesData(
            topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
            rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
            leftTitles: AxisTitles(
              sideTitles: SideTitles(
                showTitles: true, reservedSize: 40, interval: maxY / 4,
                getTitlesWidget: (v, _) => Text(money(v, compact: true, symbol: false),
                    style: const TextStyle(fontSize: 9.5, color: K.neutral400)),
              ),
            ),
            bottomTitles: AxisTitles(
              sideTitles: SideTitles(
                showTitles: true, reservedSize: 22,
                getTitlesWidget: (v, _) {
                  final i = v.toInt();
                  if (i < 0 || i >= data.length) return const SizedBox();
                  return Text('${data[i]['month']}', style: const TextStyle(fontSize: 10, color: K.neutral400));
                },
              ),
            ),
          ),
          borderData: FlBorderData(show: false),
          lineBarsData: [
            for (var s = 0; s < keys.length; s++)
              LineChartBarData(
                spots: [
                  for (var i = 0; i < data.length; i++) FlSpot(i.toDouble(), (data[i][keys[s]] as num).toDouble()),
                ],
                isCurved: true,
                barWidth: 2.4,
                color: colors[s],
                dotData: const FlDotData(show: false),
                belowBarData: BarAreaData(
                  show: true,
                  gradient: LinearGradient(
                    begin: Alignment.topCenter, end: Alignment.bottomCenter,
                    colors: [colors[s].withValues(alpha: 0.22), colors[s].withValues(alpha: 0.0)],
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class MiniBars extends StatelessWidget {
  const MiniBars({super.key, required this.data, this.color = K.primary800, this.height = 180});
  final List<Map<String, dynamic>> data;
  final Color color;
  final double height;
  @override
  Widget build(BuildContext context) {
    double maxY = 0;
    for (final r in data) {
      final v = (r['amount'] as num).toDouble();
      if (v > maxY) maxY = v;
    }
    maxY = maxY == 0 ? 1 : maxY * 1.2;
    return SizedBox(
      height: height,
      child: BarChart(
        BarChartData(
          maxY: maxY,
          gridData: FlGridData(show: true, drawVerticalLine: false, horizontalInterval: maxY / 4,
              getDrawingHorizontalLine: (_) => const FlLine(color: K.neutral200, strokeWidth: 1, dashArray: [4, 4])),
          borderData: FlBorderData(show: false),
          titlesData: FlTitlesData(
            topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
            rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
            leftTitles: AxisTitles(sideTitles: SideTitles(showTitles: true, reservedSize: 40, interval: maxY / 4,
                getTitlesWidget: (v, _) => Text(money(v, compact: true, symbol: false), style: const TextStyle(fontSize: 9.5, color: K.neutral400)))),
            bottomTitles: AxisTitles(sideTitles: SideTitles(showTitles: true, reservedSize: 20,
                getTitlesWidget: (v, _) {
              final i = v.toInt();
              if (i < 0 || i >= data.length) return const SizedBox();
              return Text('${data[i]['month']}', style: const TextStyle(fontSize: 10, color: K.neutral400));
            })),
          ),
          barGroups: [
            for (var i = 0; i < data.length; i++)
              BarChartGroupData(x: i, barRods: [
                BarChartRodData(toY: (data[i]['amount'] as num).toDouble(), color: color, width: 16, borderRadius: BorderRadius.circular(5)),
              ]),
          ],
        ),
      ),
    );
  }
}

class DonutChart extends StatelessWidget {
  const DonutChart({super.key, required this.entries, this.height = 200});
  final List<MapEntry<String, int>> entries;
  final double height;
  @override
  Widget build(BuildContext context) {
    final total = entries.fold<int>(0, (a, e) => a + e.value);
    return SizedBox(
      height: height,
      child: Row(
        children: [
          Expanded(
            flex: 3,
            child: PieChart(PieChartData(
              sectionsSpace: 2,
              centerSpaceRadius: 42,
              sections: [
                for (var i = 0; i < entries.length; i++)
                  PieChartSectionData(
                    value: entries[i].value.toDouble(),
                    color: K.chartPalette[i % K.chartPalette.length],
                    radius: 30,
                    showTitle: false,
                  ),
              ],
            )),
          ),
          Expanded(
            flex: 2,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                for (var i = 0; i < entries.length && i < 8; i++)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 2),
                    child: Row(children: [
                      Container(width: 8, height: 8, decoration: BoxDecoration(color: K.chartPalette[i % K.chartPalette.length], borderRadius: BorderRadius.circular(2))),
                      const SizedBox(width: 6),
                      Expanded(child: Text(entries[i].key, style: const TextStyle(fontSize: 11, color: K.neutral600), overflow: TextOverflow.ellipsis)),
                      Text('${entries[i].value}', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: K.neutral500)),
                    ]),
                  ),
                if (total == 0) const Text('—', style: TextStyle(color: K.neutral400)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
