<?php

namespace Superruzafa\Settings;

interface Loader
{
    /**
     * Loads settings
     *
     * @return bool
     */
    public function load();

    /**
     * Gets the loaded settings
     *
     * @return array
     */
    public function getSettings();
}
