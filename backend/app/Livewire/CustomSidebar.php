<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Facades\Filament;

class CustomSidebar extends Component
{
    public function render()
    {
        $navigation = Filament::getNavigation();

        // Build menu structure
        $menuItems = $this->buildMenuStructure($navigation);

        return view('livewire.custom-sidebar', [
            'menuItems' => $menuItems,
            'isSidebarCollapsibleOnDesktop' => Filament::isSidebarCollapsibleOnDesktop(),
            'isSidebarFullyCollapsibleOnDesktop' => Filament::isSidebarFullyCollapsibleOnDesktop(),
        ]);
    }

    public function getMenuItems(): array
    {
        $navigation = filament()->getNavigation();
        return $this->buildMenuStructure($navigation);
    }

    protected function buildMenuStructure($navigation): array
    {
        $menuItems = [];

        foreach ($navigation as $group) {
            $groupLabel = $group->getLabel();
            $groupIcon = $group->getIcon();
            $groupItems = $group->getItems();
            $isGroupCollapsible = $group->isCollapsible();
            $isGroupActive = $group->isActive();

            // If group has no label, these are top-level items
            if (blank($groupLabel)) {
                foreach ($groupItems as $item) {
                    if ($item->isVisible()) {
                        $menuItems[] = [
                            'type' => 'item',
                            'label' => $item->getLabel(),
                            'icon' => $item->getIcon(),
                            'activeIcon' => $item->getActiveIcon(),
                            'url' => $item->getUrl(),
                            'isActive' => $item->isActive(),
                            'badge' => $item->getBadge(),
                            'badgeColor' => $item->getBadgeColor(),
                            'sort' => $item->getSort(),
                        ];
                    }
                }
            } else {
                // This is a group
                $groupMenuItems = [];
                foreach ($groupItems as $item) {
                    if ($item->isVisible()) {
                        $groupMenuItems[] = [
                            'type' => 'item',
                            'label' => $item->getLabel(),
                            'icon' => $item->getIcon(),
                            'activeIcon' => $item->getActiveIcon(),
                            'url' => $item->getUrl(),
                            'isActive' => $item->isActive(),
                            'badge' => $item->getBadge(),
                            'badgeColor' => $item->getBadgeColor(),
                            'sort' => $item->getSort(),
                        ];
                    }
                }

                if (!empty($groupMenuItems)) {
                    $menuItems[] = [
                        'type' => 'group',
                        'label' => $groupLabel,
                        'icon' => $groupIcon,
                        'items' => $groupMenuItems,
                        'isCollapsible' => $isGroupCollapsible,
                        'isActive' => $isGroupActive,
                        'sort' => $group->getSort(),
                    ];
                }
            }
        }

        // Sort items
        usort($menuItems, function ($a, $b) {
            return ($a['sort'] ?? 999) <=> ($b['sort'] ?? 999);
        });

        // Sort items within groups
        foreach ($menuItems as &$menuItem) {
            if ($menuItem['type'] === 'group' && isset($menuItem['items'])) {
                usort($menuItem['items'], function ($a, $b) {
                    return ($a['sort'] ?? 999) <=> ($b['sort'] ?? 999);
                });
            }
        }

        return $menuItems;
    }
}
