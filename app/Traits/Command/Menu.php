<?php

declare(strict_types = 1);

namespace App\Traits\Command;

use NunoMaduro\LaravelConsoleMenu\MenuOption;
use PhpSchool\CliMenu\Action\GoBackAction;
use PhpSchool\CliMenu\Builder\CliMenuBuilder;
use PhpSchool\CliMenu\CliMenu;

trait Menu
{
    /**
     * @param string $title
     * @param array $options
     * @return string|null
     *
     * @throws \PhpSchool\CliMenu\Exception\InvalidTerminalException
     */
    public function menu(string $title, array $options = [])
    {
        $addMenuOption = static function (CliMenuBuilder $menuBuilder, array $options, &$optionSelected) use (&$addMenuOption) : void
        {
            foreach ($options as $value => $label) {
                if (is_array($label)) {
                    $menuBuilder->addSubMenu($value, function (CliMenuBuilder $subMenu) use ($value, $label, &$optionSelected, &$addMenuOption) {
                        $subMenu->setTitle($value);
                        $subMenu->disableDefaultItems();

                        $addMenuOption($subMenu, $label, $optionSelected);

                        $subMenu->addLineBreak('-');
                        $subMenu->addItem('Go Back', new GoBackAction);
                    });
                } else {
                    $menuBuilder->addMenuItem(
                        new MenuOption(
                            $value, $label, function (CliMenu $menu) use (&$optionSelected) {
                            $optionSelected = $menu->getSelectedItem();
                            $menu->close();
                        })
                    );
                }
            }
        };

        $menuBuilder = new CliMenuBuilder;
        $menuBuilder->setTitle($title);
        $menuBuilder->setWidth(110);
        $menuBuilder->setTitleSeparator('=');
        $menuBuilder->setForegroundColour('green');
        $menuBuilder->setBackgroundColour('black');

        $optionSelected = null;
        $addMenuOption($menuBuilder, $options, $optionSelected);

        $menuBuilder->addLineBreak('-');
        $menuBuilder->setExitButtonText('Cancel');
        $menuBuilder->build()->open();

        return $optionSelected instanceof MenuOption ? $optionSelected->getValue() : null;
    }
}
