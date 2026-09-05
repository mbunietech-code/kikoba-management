import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../app/session.dart';
import '../../i18n/strings.dart';
import '../../models/models.dart';
import '../../theme/tokens.dart';

class OtpScreen extends StatelessWidget {
  const OtpScreen({super.key});
  @override
  Widget build(BuildContext context) {
    final t = context.t;
    return Scaffold(
      appBar: AppBar(),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(t('auth.otpTitle'), style: Theme.of(context).textTheme.headlineSmall),
            const SizedBox(height: 8),
            Text(t('auth.otpSubtitle', {'target': '+255 7•• ••• 218'}), style: const TextStyle(color: K.neutral500)),
            const SizedBox(height: 28),
            Row(
              children: List.generate(6, (i) => Expanded(
                    child: Padding(
                      padding: EdgeInsets.only(right: i == 5 ? 0 : 8),
                      child: TextField(
                        textAlign: TextAlign.center,
                        maxLength: 1,
                        keyboardType: TextInputType.number,
                        style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
                        decoration: const InputDecoration(counterText: ''),
                      ),
                    ),
                  )),
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              height: 50,
              child: FilledButton(
                onPressed: () {
                  context.read<Session>().previewAs(Role.member);
                  context.go('/member');
                },
                child: Text(t('auth.otpVerify')),
              ),
            ),
            const SizedBox(height: 12),
            Center(child: TextButton(onPressed: () {}, child: Text(t('auth.otpResend')))),
          ],
        ),
      ),
    );
  }
}
