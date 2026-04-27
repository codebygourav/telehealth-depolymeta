<?php

namespace App\Filament;

use App\Traits\HasCustomSidebar;
use Filament\Facades\Filament;

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
            }
        }

        return self::processNavigation($rawItems);
    }

    /**
     * Groups and sorts the discovered items.
     */


    protected static function processNavigation(array $items): array
    {
        $navigation = [];
        $groups = [];

        foreach ($items as $item) {
            if (!$item['visible']) {
                continue;
            }

            $groupName = $item['group'] ?? null;

            if ($groupName) {
                if (!isset($groups[$groupName])) {
                    $groups[$groupName] = [
                        'type' => 'group',
                        'label' => $groupName,
                        'icon' => null,
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
            usort($group['items'], function($a, $b) {
                return ($a['sort'] ?? 99) <=> ($b['sort'] ?? 99);
            });
            $navigation[] = $group;
        }

        // Sort everything (items and groups) together by their defined sort value
        usort($navigation, function($a, $b) {
            $sortA = (int) ($a['sort'] ?? 99);
            $sortB = (int) ($b['sort'] ?? 99);
            
            if ($sortA === $sortB) {
                return strcasecmp($a['label'] ?? '', $b['label'] ?? '');
            }
            
            return $sortA <=> $sortB;
        });

        return $navigation;
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
                    ->items($groupItems)
                    ->collapsible($nav['isCollapsible'] ?? true);
            }
        }

        return $builder->groups($navigationGroups);
    }
}
