import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'tokens.dart';

ThemeData buildTheme() {
  final base = ThemeData(useMaterial3: true, brightness: Brightness.light);
  final inter = GoogleFonts.interTextTheme(base.textTheme);
  final manrope = GoogleFonts.manrope();

  return base.copyWith(
    scaffoldBackgroundColor: K.bg,
    colorScheme: ColorScheme.fromSeed(
      seedColor: K.primary800,
      primary: K.primary800,
      secondary: K.secondary600,
      surface: K.surface,
    ),
    textTheme: inter.copyWith(
      displayLarge: manrope.copyWith(fontWeight: FontWeight.w800, letterSpacing: -0.5, color: K.neutral900),
      headlineSmall: manrope.copyWith(fontWeight: FontWeight.w700, letterSpacing: -0.3, color: K.neutral900, fontSize: 22),
      titleLarge: manrope.copyWith(fontWeight: FontWeight.w700, letterSpacing: -0.2, color: K.neutral900, fontSize: 18),
      titleMedium: manrope.copyWith(fontWeight: FontWeight.w700, color: K.neutral900, fontSize: 15),
    ),
    appBarTheme: AppBarTheme(
      backgroundColor: K.surface,
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      scrolledUnderElevation: 0.5,
      centerTitle: false,
      titleTextStyle: manrope.copyWith(fontWeight: FontWeight.w700, fontSize: 18, color: K.neutral900),
      iconTheme: const IconThemeData(color: K.neutral600),
    ),
    dividerTheme: const DividerThemeData(color: K.neutral200, thickness: 1, space: 1),
    cardTheme: CardThemeData(
      color: K.surface,
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(K.radius),
        side: const BorderSide(color: K.neutral200),
      ),
      margin: EdgeInsets.zero,
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: K.surface,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: K.neutral300)),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: K.neutral300)),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: K.primary500, width: 1.6)),
      hintStyle: const TextStyle(color: K.neutral400),
      labelStyle: const TextStyle(color: K.neutral600),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: K.primary800,
        foregroundColor: Colors.white,
        elevation: 0,
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        foregroundColor: K.neutral700,
        side: const BorderSide(color: K.neutral300),
        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 13),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(foregroundColor: K.primary700, textStyle: const TextStyle(fontWeight: FontWeight.w600)),
    ),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: K.surface,
      indicatorColor: K.primary50,
      elevation: 0,
      height: 64,
      labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
      iconTheme: WidgetStateProperty.resolveWith((s) => IconThemeData(
            color: s.contains(WidgetState.selected) ? K.primary800 : K.neutral400, size: 22)),
      labelTextStyle: WidgetStateProperty.resolveWith((s) => TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w600,
            color: s.contains(WidgetState.selected) ? K.primary800 : K.neutral500,
          )),
    ),
    snackBarTheme: SnackBarThemeData(
      behavior: SnackBarBehavior.floating,
      backgroundColor: K.neutral900,
      contentTextStyle: const TextStyle(color: Colors.white),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
    ),
  );
}
