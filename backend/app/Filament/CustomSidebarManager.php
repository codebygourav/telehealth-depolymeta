<?php

namespace App\Filament;

use Filament\Facades\Filament;
use Illuminate\Support\Str;

class CustomSidebarManager
{
    /**
     * Get processed navigation for the custom Blade sidebar.
     * This method automatically discovers all Resources and Pages
     * that implement the getCustomSidebarItem() method.
     */
    /**
     * Get processed navigation for the custom Blade sidebar.
     */
    public static function getNavigation(): array
    {
        $panel = Filament::getPanel('admin');

        $classes = array_merge(
            $panel->getResources(),
            $panel->getPages()
        );

        $rawItems = [];

        foreach ($classes as $class) {
            if (method_exists($class, 'getCustomSidebarItem')) {
                $rawItems[] = $class::getCustomSidebarItem();
                continue;
            }

            $rawItems[] = self::makeSidebarItemFromClass($class);
        }

        return self::processNavigation(array_filter($rawItems));
    }

    protected static function makeSidebarItemFromClass(string $class): ?array
    {
        if (! class_exists($class)) {
            return null;
        }

        $vars = get_class_vars($class);
        $label = null;

        if (method_exists($class, 'getNavigationLabel')) {
            $label = $class::getNavigationLabel();
        }

        if (! $label && method_exists($class, 'getTitle')) {
            $label = $class::getTitle();
        }

        $label ??= Str::of(class_basename($class))
            ->replace(['Resource', 'Page'], '')
            ->headline()
            ->toString();

        $url = '#';
        if (method_exists($class, 'getUrl')) {
            try {
                $url = $class::getUrl();
            } catch (\Throwable $exception) {
                $url = '#';
            }
        }

        $isActive = false;
        if (method_exists($class, 'getRouteBaseName')) {
            try {
                $base = $class::getRouteBaseName();
                $isActive = $base ? request()->routeIs($base . '.*') : false;
            } catch (\Throwable $exception) {
                $isActive = false;
            }
        }

        if (! $isActive) {
            $slug = method_exists($class, 'getSlug')
                ? $class::getSlug()
                : ($vars['slug'] ?? Str::of(class_basename($class))->kebab()->toString());

            $isActive = request()->is('admin/' . $slug . '*');
        }

        return [
            'class' => $class,
            'type' => 'item',
            'label' => (string) $label,
            'icon' => method_exists($class, 'getNavigationIcon') ? $class::getNavigationIcon() : ($vars['navigationIcon'] ?? null),
            'activeIcon' => $vars['activeNavigationIcon'] ?? null,
            'url' => $url,
            'isActive' => $isActive,
            'visible' => true,
            'sort' => method_exists($class, 'getNavigationSort') && is_numeric($class::getNavigationSort())
                ? (int) $class::getNavigationSort()
                : (int) ($vars['navigationSort'] ?? 99),
            'group' => method_exists($class, 'getNavigationGroup') ? $class::getNavigationGroup() : ($vars['navigationGroup'] ?? null),
            'isCollapsible' => true,
        ];
    }

    /**
     * Groups and sorts the discovered items.
     */


    protected static function processNavigation(array $items): array
    {
        $navigation = [];
        $groups = [];

        foreach ($items as $item) {
            if (!self::isItemVisible($item)) {
                continue;
            }

            $groupName = $item['group'] ?? null;

            if ($groupName) {
                if (!isset($groups[$groupName])) {
                    $groups[$groupName] = [
                        'type' => 'group',
                        'label' => $groupName,
                        'icon' => self::getGroupIcon($groupName),
                        'isCollapsible' => $item['isCollapsible'] ?? true,
                        // Group sort is the min sort of its items
                        'sort' => $item['sort'] ?? 99,
                        'items' => [],
                    ];
                }

                if (isset($item['sort']) && $item['sort'] < $groups[$groupName]['sort']) {
                    $groups[$groupName]['sort'] = $item['sort'];
                }

                $groups[$groupName]['items'][] = $item;
            } else {
                $item['type'] = 'item';
                $navigation[] = $item;
            }
        }

        // Add groups to the navigation list
        foreach ($groups as $group) {
            usort($group['items'], function ($a, $b) {
                return ($a['sort'] ?? 99) <=> ($b['sort'] ?? 99);
            });
            $navigation[] = $group;
        }

        // Sort everything (items and groups) together by their defined sort value
        usort($navigation, function ($a, $b) {
            $sortA = (int) ($a['sort'] ?? 99);
            $sortB = (int) ($b['sort'] ?? 99);

            if ($sortA === $sortB) {
                return strcasecmp($a['label'] ?? '', $b['label'] ?? '');
            }

            return $sortA <=> $sortB;
        });

        return $navigation;
    }

    protected static function getGroupIcon(string $groupName): string
    {
        return match ($groupName) {
            'Appointments & Finance' => 'heroicon-o-calendar-days',
            'Doctor Management' => 'heroicon-o-user-group',
            'User Management' => 'heroicon-o-users',
            'Media' => 'heroicon-o-photo',
            'Reports' => 'heroicon-o-chart-bar',
            'System & Settings' => 'heroicon-o-cog-6-tooth',
            'Token Queue Display' => 'heroicon-o-tv',
            'Vaccination' => 'heroicon-o-shield-check',
            'Diet' => 'heroicon-o-heart',
            'Medicine' => 'heroicon-o-beaker',
            default => 'heroicon-o-folder',
        };
    }

    /**
     * Central sidebar visibility gate.
     *
     * This keeps future menu additions permission-aware even if a resource/page
     * accidentally sets a custom visible flag.
     */
    protected static function isItemVisible(array $item): bool
    {
        if (empty($item['visible'])) {
            return false;
        }

        $class = $item['class'] ?? null;

        if (!is_string($class) || !class_exists($class)) {
            return (bool) $item['visible'];
        }

        if (method_exists($class, 'canViewAny') && ! $class::canViewAny()) {
            return false;
        }

        if (method_exists($class, 'shouldRegisterNavigation') && ! $class::shouldRegisterNavigation()) {
            return false;
        }

        if (method_exists($class, 'canAccess') && ! $class::canAccess()) {
            return false;
        }

        return true;
    }

    /**
     * Build standard Filament navigation using our custom rules.
     */
    public static function buildFilamentNavigation(\Filament\Navigation\NavigationBuilder $builder): \Filament\Navigation\NavigationBuilder
    {
        $items = self::getNavigation();

        $navigationGroups = [];

        foreach ($items as $nav) {
            if ($nav['type'] === 'item') {
                // Return a group with no label to show as a parent menu item
                $navigationGroups[] = \Filament\Navigation\NavigationGroup::make()
                    ->items([
                        \Filament\Navigation\NavigationItem::make($nav['label'])
                            ->icon($nav['icon'])
                            ->activeIcon($nav['activeIcon'] ?? null)
                            ->url($nav['url'])
                            ->isActiveWhen(fn() => $nav['isActive'])
                    ]);
            } elseif ($nav['type'] === 'group') {
                $groupItems = [];
                foreach ($nav['items'] as $item) {
                    $groupItems[] = \Filament\Navigation\NavigationItem::make($item['label'])
                        ->icon($item['icon'])
                        ->activeIcon($item['activeIcon'] ?? null)
                        ->url($item['url'])
                        ->isActiveWhen(fn() => $item['isActive']);
                }

                $navigationGroups[] = \Filament\Navigation\NavigationGroup::make($nav['label'])
                    ->icon($nav['icon'] ?? null)
                    ->items($groupItems)
                    ->collapsible($nav['isCollapsible'] ?? true);
            }
        }

        return $builder->groups($navigationGroups);
    }
}
