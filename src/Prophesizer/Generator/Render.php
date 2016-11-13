<?php

namespace Prophesizer\Generator;

class Render
{
    /**
     * @param string   $template
     * @param string[] $replacements
     * @return string
     */
    public static function applyReplacements($template, array $replacements)
    {
        $output = $template;
        foreach ($replacements as $placeholder => $value) {
            $output = str_replace('{{'.$placeholder.'}}', $value, $output);
        }

        return $output;
    }
}
