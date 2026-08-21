<?php

use App\Models\ConfiguracionModel;

if (!function_exists('get_configuracion')) {
    function get_configuracion()
    {
        $model = new ConfiguracionModel();
        return $model->getConfig();
    }
}
