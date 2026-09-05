import 'package:flutter/material.dart';

/// Benja Kikoba design tokens.
/// Primary #115E59 (teal) · Secondary #2563EB (blue) · Tertiary #16A34A (green) · Neutral #0F172A (slate)
class K {
  // Primary — teal
  static const primary50 = Color(0xFFF0FDFA);
  static const primary100 = Color(0xFFCCFBF1);
  static const primary200 = Color(0xFF99F6E4);
  static const primary300 = Color(0xFF5EEAD4);
  static const primary500 = Color(0xFF14B8A6);
  static const primary600 = Color(0xFF0D9488);
  static const primary700 = Color(0xFF0F766E);
  static const primary800 = Color(0xFF115E59);
  static const primary900 = Color(0xFF134E4A);

  // Secondary — blue
  static const secondary50 = Color(0xFFEFF6FF);
  static const secondary100 = Color(0xFFDBEAFE);
  static const secondary600 = Color(0xFF2563EB);
  static const secondary700 = Color(0xFF1D4ED8);

  // Tertiary — green
  static const tertiary50 = Color(0xFFF0FDF4);
  static const tertiary100 = Color(0xFFDCFCE7);
  static const tertiary600 = Color(0xFF16A34A);
  static const tertiary700 = Color(0xFF15803D);

  // Neutral — slate
  static const neutral50 = Color(0xFFF8FAFC);
  static const neutral100 = Color(0xFFF1F5F9);
  static const neutral200 = Color(0xFFE2E8F0);
  static const neutral300 = Color(0xFFCBD5E1);
  static const neutral400 = Color(0xFF94A3B8);
  static const neutral500 = Color(0xFF64748B);
  static const neutral600 = Color(0xFF475569);
  static const neutral700 = Color(0xFF334155);
  static const neutral800 = Color(0xFF1E293B);
  static const neutral900 = Color(0xFF0F172A);

  // Status
  static const success = Color(0xFF16A34A);
  static const warning = Color(0xFFD97706);
  static const danger = Color(0xFFDC2626);
  static const info = Color(0xFF2563EB);
  static const purple = Color(0xFF7C3AED);

  static const bg = neutral100;
  static const surface = Colors.white;

  static const radius = 16.0;
  static const radiusSm = 10.0;
  static const radiusLg = 24.0;

  static final cardShadow = [
    BoxShadow(color: const Color(0xFF0F172A).withValues(alpha: 0.04), blurRadius: 2, offset: const Offset(0, 1)),
    BoxShadow(color: const Color(0xFF0F172A).withValues(alpha: 0.06), blurRadius: 16, offset: const Offset(0, 4)),
  ];
  static final popShadow = [
    BoxShadow(color: const Color(0xFF0F172A).withValues(alpha: 0.18), blurRadius: 32, offset: const Offset(0, 8)),
  ];

  static const chartPalette = [
    primary800, secondary600, tertiary600, warning, danger, purple, primary600, neutral500,
  ];
}
