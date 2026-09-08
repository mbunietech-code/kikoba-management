<?php

namespace App\Support;

class Nav
{
    /** @return array<int, array{title:string, items: array<int, array{route:string, label:string, icon:string, can?:string}>}> */
    public static function admin(): array
    {
        $f = app()->bound('kikoba.features') ? app('kikoba.features') : Features::MODULES;

        $membership = [
            ['route' => 'admin.members.index', 'label' => 'nav.members', 'icon' => 'users', 'can' => 'members.view'],
        ];
        if ($f['opening_shares'] ?? true) {
            $membership[] = ['route' => 'admin.opening-shares.index', 'label' => 'nav.openingShares', 'icon' => 'sparkles', 'can' => 'shares.view'];
        }
        if ($f['shares'] ?? true) {
            $membership[] = ['route' => 'admin.shares.index', 'label' => 'nav.shares', 'icon' => 'chart-pie', 'can' => 'shares.view'];
        }
        if ($f['community'] ?? true) {
            $membership[] = ['route' => 'admin.community.index', 'label' => 'nav.community', 'icon' => 'user-group', 'can' => 'members.view'];
        }
        if ($f['insurance'] ?? true) {
            $membership[] = ['route' => 'admin.insurance.index', 'label' => 'nav.insurance', 'icon' => 'shield-check', 'can' => 'insurance.view'];
        }

        return [
            ['title' => 'nav.groupsMain', 'items' => [
                ['route' => 'admin.dashboard', 'label' => 'nav.dashboard', 'icon' => 'squares-2x2'],
            ]],
            ['title' => 'nav.groupsMembers', 'items' => $membership],
            ['title' => 'nav.groupsFinance', 'items' => [
                ['route' => 'admin.savings.index', 'label' => 'nav.savings', 'icon' => 'banknotes', 'can' => 'savings.view'],
                ['route' => 'admin.loans.index', 'label' => 'nav.loans', 'icon' => 'credit-card', 'can' => 'loans.view'],
                ['route' => 'admin.products.index', 'label' => 'nav.loanProducts', 'icon' => 'rectangle-stack', 'can' => 'products.view'],
                ['route' => 'admin.guarantors.index', 'label' => 'nav.guarantors', 'icon' => 'user-group', 'can' => 'guarantors.view'],
                ['route' => 'admin.projects.index', 'label' => 'nav.projects', 'icon' => 'briefcase', 'can' => 'projects.view'],
                ['route' => 'admin.payments.index', 'label' => 'nav.payments', 'icon' => 'wallet', 'can' => 'payments.view'],
                ['route' => 'admin.accounting.index', 'label' => 'nav.accounting', 'icon' => 'calculator', 'can' => 'accounting.view'],
                ['route' => 'admin.profit.index', 'label' => 'nav.profitDistribution', 'icon' => 'arrow-trending-up', 'can' => 'profit.view'],
            ]],
            ['title' => 'nav.groupsAdmin', 'items' => [
                ['route' => 'admin.reports.index', 'label' => 'nav.reports', 'icon' => 'chart-bar', 'can' => 'reports.view'],
                ['route' => 'admin.transactions.index', 'label' => 'nav.transactions', 'icon' => 'arrows-right-left', 'can' => 'reports.view'],
                ['route' => 'admin.notifications.index', 'label' => 'nav.notifications', 'icon' => 'bell'],
                ['route' => 'admin.users.index', 'label' => 'nav.users', 'icon' => 'user-plus', 'can' => 'users.view'],
                ['route' => 'admin.roles.index', 'label' => 'nav.roles', 'icon' => 'shield-exclamation', 'can' => 'roles.manage'],
                ['route' => 'admin.settings.index', 'label' => 'nav.settings', 'icon' => 'cog-6-tooth', 'can' => 'settings.view'],
                ['route' => 'admin.audit.index', 'label' => 'nav.auditLogs', 'icon' => 'clock', 'can' => 'audit.view'],
            ]],
        ];
    }

    public static function member(): array
    {
        $f = app()->bound('kikoba.features') ? app('kikoba.features') : Features::MODULES;

        $main = [
            ['route' => 'member.home', 'label' => 'nav.home', 'icon' => 'home'],
        ];
        if ($f['shares'] ?? true) {
            $main[] = ['route' => 'member.shares', 'label' => 'nav.myShares', 'icon' => 'chart-pie'];
        }
        $main[] = ['route' => 'member.savings', 'label' => 'nav.mySavings', 'icon' => 'banknotes'];
        $main[] = ['route' => 'member.loans', 'label' => 'nav.myLoans', 'icon' => 'credit-card'];
        $main[] = ['route' => 'member.repayments', 'label' => 'nav.repayments', 'icon' => 'wallet'];

        $finance = [
            ['route' => 'member.projects', 'label' => 'nav.myProjects', 'icon' => 'briefcase'],
        ];
        if ($f['insurance'] ?? true) {
            $finance[] = ['route' => 'member.insurance', 'label' => 'nav.myInsurance', 'icon' => 'shield-check'];
        }
        $finance[] = ['route' => 'member.transactions', 'label' => 'nav.transactions', 'icon' => 'arrows-right-left'];
        $finance[] = ['route' => 'member.statements', 'label' => 'nav.statements', 'icon' => 'document-text'];

        return [
            ['title' => 'nav.groupsMain', 'items' => $main],
            ['title' => 'nav.groupsFinance', 'items' => $finance],
            ['title' => 'nav.groupsAdmin', 'items' => [
                ['route' => 'member.notifications', 'label' => 'nav.notifications', 'icon' => 'bell'],
                ['route' => 'member.profile', 'label' => 'common.profile', 'icon' => 'user'],
            ]],
        ];
    }
}
