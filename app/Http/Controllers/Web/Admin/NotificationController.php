<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Support\Audit;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $orgId = app('kikoba.org')->id;
        $notifications = Notification::where('organization_id', $orgId)->with('member:id,full_name')
            ->latest('created_at')->paginate(30);
        $unread = Notification::where('organization_id', $orgId)->whereNull('read_at')->count();

        return view('admin.notifications.index', compact('notifications', 'unread'));
    }

    public function readAll()
    {
        Notification::where('organization_id', app('kikoba.org')->id)->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('toast', t('notifications.markAllRead').' ✓');
    }

    public function announce(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
        ]);
        // Only in-app delivery exists today; SMS/e-mail sending is not wired up.
        $n = Notification::create([...$data, 'channel' => 'in_app', 'organization_id' => app('kikoba.org')->id, 'type' => 'announcement']);
        Audit::log($request, 'SEND_ANNOUNCEMENT', 'Notification', $n->id);

        return back()->with('toast', t('notifications.announcementPosted'));
    }

    public function destroy(Request $request, Notification $notification)
    {
        $notification->delete();

        return back()->with('toast', t('common.deleted'));
    }
}
