import 'package:flutter/widgets.dart';
import 'package:provider/provider.dart';
import '../../app/session.dart';
import '../../data/mock_data.dart';
import '../../models/models.dart';

String currentMemberId(BuildContext context) =>
    context.watch<Session>().memberId ?? mock.currentMemberId;

Member currentMember(BuildContext context) => mock.memberById(currentMemberId(context))!;
