<?php

namespace Sanjab\Helpers;

use Illuminate\Support\Str;

/**
 * Holder of CRUD controller properties.
 *
 * @method $this model (string $val)                 Model to handle.
 * @method $this route (string $val)                 Path of crud module.
 * @method $this title (string $val)                 Title of crud.
 * @method $this titles (string $val)                Title of crud (plural).
 * @method $this description (string $val)           short description about crud.
 * @method $this icon (string $val)                  Icon of crud.
 * @method $this creatable (boolean $val)            Can create new.
 * @method $this showable (boolean $val)             Can show items.
 * @method $this editable (boolean $val)             Can edit items.
 * @method $this deletable (boolean $val)            Can delete items.
 * @method $this perPage (integer $val)              Default per page.
 * @method $this perPages (array $val)               Array of per pages.
 * @method $this defaultOrder (string $val)          Default column order.
 * @method $this defaultOrderDirection (string $val) Default column order direction.
 * @method $this permissionsKey (string $val)        Permission key if you use more than one crud controller for one model.
 * @method $this bulk (bool $val)                    Is bulk actions allowed or not.
 * @method $this defaultCards(bool $val)             Should default cards added to cards.
 * @method $this defaultDashboardCards(bool $val)    Should default dashboard cards added to dashboard.
 * @method $this globalSearch(bool $val)             Should be present in global search or not.
 * @method $this itemFormat(string $val)             Format to show as item id. (example: "%id. %name")
 */
class CrudProperties extends PropertiesHolder
{
    protected $properties = [
        'icon' => 'code'
    ];

    public function __construct(array $options = [])
    {
        $this->perPages([10 => 10, 20 => 20, 50 => 50, 100 => 100, PHP_INT_MAX => trans('sanjab::sanjab.all')]);
        $this->perPage(20);
        $this->creatable(true);
        $this->showable(true);
        $this->editable(true);
        $this->deletable(true);
        $this->bulk(true);
        $this->defaultOrder('id');
        $this->defaultOrderDirection('desc');
        $this->permissionsKey('');
        $this->defaultCards(true);
        $this->defaultDashboardCards(true);
        $this->globalSearch(true);
        parent::__construct($options);
    }

    /**
     * create new Menu item
     *
     * @return static
     */
    public static function create($route = null)
    {
        $out = new static;
        if ($route) {
            $out->route($route);
        }
        $out->title(Str::singular(Str::title($route)));
        $out->titles(Str::title($route));

        return $out;
    }
}
