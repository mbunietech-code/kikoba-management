<?php

namespace App\Support;

class Nav
{
    /** @return array<int, array{title:string, items: array<int, array{route:string, label:string, icon:string, can?:string}>}> */
    public static function admin(): array
    {
        return [
            ['title' => 'nav.groupsMain', 'items' => [
                ['route' => 'admin.dashboard', 'label' => 'nav.dashboard', 'icon' => 'squares-2x2'],
            ]],
            ['title' => 'nav.groupsMembers', 'items' => [
                ['route' => 'admin.members.index', 'label' => 'nav.members', 'icon' => 'users', 'can' => 'members.view'],
                ['route' => 'admin.shares.index', 'label' => 'nav.shares', 'icon' => 'chart-pie', 'can' => 'shares.view'],
                ['route' => 'admin.opening-shares.index', 'label' => 'nav.openingShares', 'icon' => 'sparkles', 'can' => 'shares.view'],
                ['route' => 'admin.insurance.index', 'label' => 'nav.insurance', 'icon' => 'shield-check', 'can' => 'insurance.view'],
            ]],
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
        return [
            ['title' => 'nav.groupsMain', 'items' => [
                ['route' => 'member.home', 'label' => 'nav.home', 'icon' => 'home'],
                ['route' => 'member.shares', 'label' => 'nav.myShares', 'icon' => 'chart-pie'],
                ['route' => 'member.savings', 'label' => 'nav.mySavings', 'icon' => 'banknotes'],
                ['route' => 'member.loans', 'label' => 'nav.myLoans', 'icon' => 'credit-card'],
                ['route' => 'member.repayments', 'label' => 'nav.repayments', 'icon' => 'wallet'],
            ]],
            ['title' => 'nav.groupsFinance', 'items' => [
                ['route' => 'member.projects', 'label' => 'nav.myProjects', 'icon' => 'briefcase'],
                ['route' => 'member.insurance', 'label' => 'nav.myInsurance', 'icon' => 'shield-check'],
                ['route' => 'member.transactions', 'label' => 'nav.transactions', 'icon' => 'arrows-right-left'],
                ['route' => 'member.statements', 'label' => 'nav.statements', 'icon' => 'document-text'],
            ]],
            ['title' => 'nav.groupsAdmin', 'items' => [
                ['route' => 'member.notifications', 'label' => 'nav.notifications', 'icon' => 'bell'],
                ['route' => 'member.profile', 'label' => 'common.profile', 'icon' => 'user'],
            ]],
        ];
    }
}
