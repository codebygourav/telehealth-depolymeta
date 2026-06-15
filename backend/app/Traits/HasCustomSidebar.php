<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasCustomSidebar
{
    /**
     * Define sidebar options in one place. 
     * Override this in your Resource or Page for full control.
     */
    public static function getSidebarOptions(): array
    {
        return [
            'label'         => null, // Falls back to Headline name
            'icon'          => null, // Falls back to getNavigationIcon()
            'activeIcon'    => null, // Falls back to activeNavigationIcon property
            'group'         => null, // Parent Group Name
            'sort'          => null, // Integer order
            'isCollapsible' => true,
            'visible'       => null, // Default: depends on permissions
        ];
    }

    public static function getCustomSidebarItem(): array
    {
        $options = static::getSidebarSettings();

        return [
            'class'         => static::class,
            'type'          => 'item',
            'label'         => $options['label'] ?? static::getSidebarLabel(),
            'icon'          => $options['icon'] ?? static::getSidebarIcon(),
            'activeIcon'    => $options['activeIcon'] ?? static::getSidebarActiveIcon(),
            'url'           => static::getSidebarUrl(),
            'isActive'      => static::isSidebarItemActive(),
            'visible'       => $options['visible'] ?? static::isSidebarItemVisible(),
            'sort'          => $options['sort'] ?? static::getSidebarSort(),
            'group'         => $options['group'] ?? static::getSidebarGroup(),
            'isCollapsible' => $options['isCollapsible'] ?? static::isSidebarGroupCollapsible(),
        ];
    }

    protected static function getSidebarSettings(): array
    {
        $defaults = [
            'label'         => null,
            'icon'          => null,
            'activeIcon'    => null,
            'group'         => null,
            'sort'          => null,
            'isCollapsible' => true,
            'visible'       => null,
        ];

        return array_merge($defaults, method_exists(static::class, 'getSidebarOptions') ? static::getSidebarOptions() : []);
    }

    protected static function getSidebarLabel(): string
    {
        $label = null;
        if (method_exists(static::class, 'getNavigationLabel')) {
            $label = static::getNavigationLabel();
        }
        if (!$label && method_exists(static::class, 'getTitle')) {
            $label = static::getTitle();
        }
        if (!$label) {
            $vars = get_class_vars(static::class);
            $label = $vars['title'] ?? null;
        }
        return (string) ($label ?? Str::of(class_basename(static::class))->replace('Resource', '')->headline());
    }

    protected static function getSidebarIcon(): string|\Illuminate\Contracts\Support\Htmlable|\BackedEnum|null
    {
        if (method_exists(static::class, 'getNavigationIcon')) {
            return static::getNavigationIcon();
        }
        $vars = get_class_vars(static::class);
        return $vars['navigationIcon'] ?? null;
    }

    protected static function getSidebarActiveIcon(): string|\Illuminate\Contracts\Support\Htmlable|\BackedEnum|null
    {
        $vars = get_class_vars(static::class);
        return $vars['activeNavigationIcon'] ?? null;
    }

    protected static function getSidebarUrl(): string
    {
        return method_exists(static::class, 'getUrl') ? static::getUrl() : '#';
    }

    protected static function isSidebarItemActive(): bool
    {
        if (method_exists(static::class, 'getRouteBaseName')) {
            $base = static::getRouteBaseName();
            if ($base) {
                try {
                    return request()->routeIs($base . '.*');
                } catch (\Exception $e) {}
            }
        }

        $slug = null;
        if (method_exists(static::class, 'getSlug')) {
            $slug = static::getSlug();
        } else {
            $vars = get_class_vars(static::class);
            $slug = $vars['slug'] ?? (string) str(class_basename(static::class))->kebab();
        }

        return request()->is('admin/' . $slug . '*');
    }

    protected static function isSidebarItemVisible(): bool
    {
        if (method_exists(static::class, 'canViewAny')) {
            return (bool) static::canViewAny();
        }
        if (method_exists(static::class, 'shouldRegisterNavigation')) {
            return (bool) static::shouldRegisterNavigation();
        }
        if (method_exists(static::class, 'canAccess')) {
            return (bool) static::canAccess();
        }
        return true;
    }

    protected static function getSidebarSort(): int|float
    {
        if (method_exists(static::class, 'getNavigationSort')) {
            $sort = static::getNavigationSort();
            return is_numeric($sort) ? (int) $sort : 99;
        }
        return 99;
    }

    protected static function getSidebarGroup(): string|\Illuminate\Contracts\Support\Htmlable|\BackedEnum|null
    {
        if (method_exists(static::class, 'getNavigationGroup')) {
            return static::getNavigationGroup();
        }
        return null;
    }

    protected static function isSidebarGroupCollapsible(): bool
    {
        return true;
    }
}
