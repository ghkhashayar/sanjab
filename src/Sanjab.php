<?php

namespace Sanjab;

use Exception;
use Sanjab\Helpers\MenuItem;
use Sanjab\Helpers\PermissionItem;
use Illuminate\Support\Facades\Auth;
use Sanjab\Helpers\NotificationItem;
use Sanjab\Cards\Card;
use Sanjab\Helpers\SearchResult;
use Illuminate\Support\Facades\Cache;

class Sanjab
{
    const SANJAB_CONTROLLERS = [
        \Sanjab\Controllers\AuthController::class,
        \Sanjab\Controllers\RoleController::class,
        \Sanjab\Controllers\DropzoneController::class,
        \Sanjab\Controllers\QuillController::class,
        \Sanjab\Controllers\RelationWidgetController::class,
        \Sanjab\Controllers\CheckboxWidgetController::class,
        \Sanjab\Controllers\TranslationController::class,
        \Sanjab\Controllers\SearchController::class,
    ];

    /**
     * Menu items
     *
     * @var MenuItem[]
     */
    protected static $menuItems = null;

    /**
     * Menu items
     *
     * @var NotificationItem[]
     */
    protected static $notificationItems = null;

    /**
     * Permission items
     *
     * @var MenuItem[]
     */
    protected static $permissionItems = null;

    /**
     * Dashboard cards
     *
     * @var Card[]
     */
    protected static $dashboardCards = null;

    /**
     * Array of controllers.
     *
     * @return array
     */
    public static function controllers()
    {
        return array_merge(config('sanjab.controllers'), static::SANJAB_CONTROLLERS);
    }

    /**
     * All controllers menu items.
     *
     * @return MenuItem[]
     * @throws Exception
     */
    public static function menuItems(): array
    {
        if (static::$menuItems == null) {
            static::$menuItems = [];
            if (Auth::check()) {
                foreach (static::controllers() as $controller) {
                    foreach ($controller::menus() as $menuItem) {
                        if (! $menuItem instanceof MenuItem) {
                            throw new Exception("Some menu item in '$controller' is not a MenuItem type.");
                        }
                        if ($menuItem->hasChildren() == false || ! isset(static::$menuItems[$menuItem->title])) {
                            static::$menuItems[$menuItem->title] = $menuItem;
                        } else {
                            foreach ($menuItem->getChildren() as $childItem) {
                                static::$menuItems[$menuItem->title]
                                    ->addChild($childItem);
                            }
                        }
                    }
                }
                static::$menuItems = array_filter(static::$menuItems, function ($menuItem) {
                    return !$menuItem->isHidden();
                });
                usort(static::$menuItems, function ($a, $b) {
                    return $a->order > $b->order;
                });
                static::$menuItems = array_values(static::$menuItems);
            }
        }
        return static::$menuItems;
    }

    /**
     * All controllers menu items.
     *
     * @return NotificationItem[]
     * @throws Exception
     */
    public static function notificationItems(): array
    {
        if (static::$notificationItems == null) {
            static::$notificationItems = [];
            if (Auth::check()) {
                foreach (static::controllers() as $controller) {
                    foreach ($controller::notifications() as $notificationItem) {
                        if (! $notificationItem instanceof NotificationItem) {
                            throw new Exception("Some permission item in '$controller' is not a NotificationItem type.");
                        }
                        static::$notificationItems[] = $notificationItem;
                    }
                }
                static::$notificationItems = array_filter(static::$notificationItems, function ($menuItem) {
                    return !$menuItem->isHidden();
                });
                usort(static::$notificationItems, function ($a, $b) {
                    return $a->order > $b->order;
                });
            }
        }
        return static::$notificationItems;
    }

    /**
     * All controllers permission items.
     *
     * @return PermissionItem[]
     * @throws Exception
     */
    public static function permissionItems(): array
    {
        if (static::$permissionItems == null) {
            static::$permissionItems = [];
            foreach (static::controllers() as $controller) {
                foreach ($controller::permissions() as $permissionItem) {
                    if (! $permissionItem instanceof PermissionItem) {
                        throw new Exception("Some permission item in '$controller' is not a PermissionItem type.");
                    }
                    if (! isset(static::$permissionItems[$permissionItem->groupName])) {
                        static::$permissionItems[$permissionItem->groupName] = $permissionItem;
                    } else {
                        foreach ($permissionItem->permissions() as $permission) {
                            static::$permissionItems[$permissionItem->groupName]
                                ->addPermission($permission['title'], $permission['name'], $permission['model']);
                        }
                    }
                }
            }
            static::$permissionItems = array_values(static::$permissionItems);
            usort(static::$permissionItems, function ($a, $b) {
                return $a->order > $b->order;
            });
        }
        return static::$permissionItems;
    }

    /**
     * All controllers permission items.
     *
     * @return Card[]
     * @throws Exception
     */
    public static function dashboardCards(): array
    {
        if (static::$dashboardCards == null) {
            static::$dashboardCards = [];
            foreach (static::controllers() as $controller) {
                foreach ($controller::dashboardCards() as $dashboardCard) {
                    if (! $dashboardCard instanceof Card) {
                        throw new Exception("Some dashboard card item in '$controller' is not a Card type.");
                    }
                    static::$dashboardCards[] = $dashboardCard;
                }
            }
            usort(static::$dashboardCards, function ($a, $b) {
                return $a->order > $b->order;
            });
        }
        return static::$dashboardCards;
    }

    /**
     * Search globally in all controllers.
     *
     * @return SearchResult[]
     * @throws Exception
     */
    public static function search(string $search): array
    {
        $results = [];
        foreach (static::controllers() as $controller) {
            foreach ($controller::globalSearch($search) as $searchResult) {
                if (! $searchResult instanceof SearchResult) {
                    throw new Exception("Some search result in '$controller' is not a SearchResult type.");
                }
                $searchResult->setProperty('search', $search);
                $results[] = $searchResult;
                if (count($results) > 50) {
                    break 2;
                }
            }
        }
        usort($results, function ($a, $b) {
            return $a->order > $b->order;
        });
        return $results;
    }

    /**
     * Random image info for background.
     *
     * @return array|null
     */
    public static function image()
    {
        return Cache::remember('sanjab_background_details', now()->addHours(6), function () {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://sanjabteam.github.io/unsplash/images.json",
                CURLOPT_TIMEOUT => 30,
                CURLOPT_RETURNTRANSFER => true,
            ]);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                return null;
            } else {
                $response = json_decode($response, true);
                $out = array_random($response);
                if (is_array($out) && isset($out['image']) && isset($out['link']) && isset($out['author'])) {
                    return $out;
                }
                return null;
            }
        });
    }
}
