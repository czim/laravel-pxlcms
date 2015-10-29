<?php
namespace Czim\PxlCms\Generator\Writer\Model\Steps;

class StubReplaceAttributeData extends AbstractProcessStep
{

    protected function process()
    {
        $this->stubPregReplace('# *{{FILLABLE}}\n?#i', $this->getFillableReplace())
             ->stubPregReplace('# *{{TRANSLATED}}\n?#i', $this->getTranslatedReplace())
             ->stubPregReplace('# *{{HIDDEN}}\n?#i', $this->getHiddenReplace())
             ->stubPregReplace('# *{{CASTS}}\n?#i', $this->getCastsReplace())
             ->stubPregReplace('# *{{DATES}}\n?#i', $this->getDatesReplace())
             ->stubPregReplace('# *{{DEFAULTS}}\n?#i', $this->getDefaultsReplace());
    }


    /**
     * Returns the replacement for the fillable placeholder
     *
     * @return string
     */
    protected function getFillableReplace()
    {
        $attributes = array_merge(
            $this->data['normal_fillable'],
            $this->data['translated_fillable']
        );

        if ( ! count($attributes)) return '';

        return $this->getAttributePropertySection('fillable', $attributes);
    }

    /**
     * Returns the replacement for the translated placeholder
     *
     * @return string
     */
    protected function getTranslatedReplace()
    {
        $attributes = $this->data['translated_attributes'] ?: [];

        if ( ! count($attributes)) return '';

        return $this->getAttributePropertySection('translatedAttributes', $attributes);
    }

    /**
     * Returns the replacement for the hidden placeholder
     *
     * @return string
     */
    protected function getHiddenReplace()
    {
        $attributes = $this->data['hidden'] ?: [];

        if ( ! count($attributes)) return '';

        return $this->getAttributePropertySection('hidden', $attributes);
    }

    /**
     * Returns the replacement for the casts placeholder
     *
     * @return string
     */
    protected function getCastsReplace()
    {
        $attributes = $this->data['casts'] ?: [];

        if ( ! count($attributes)) return '';

        // align assignment signs by longest attribute
        $longestLength = 0;

        foreach ($attributes as $attribute => $type) {

            if (strlen($attribute) > $longestLength) {
                $longestLength = strlen($attribute);
            }
        }

        $replace = $this->tab() . "protected \$casts = [\n";

        foreach ($attributes as $attribute => $type) {

            $replace .= $this->tab(2)
                . "'" . str_pad($attribute . "'", $longestLength + 1)
                . " => '" . $type . "',\n";
        }

        $replace .= $this->tab() . "];\n\n";

        return $replace;
    }

    /**
     * Returns the replacement for the dates placeholder
     *
     * @return string
     */
    protected function getDatesReplace()
    {
        $attributes = $this->data['dates'] ?: [];

        if ( ! count($attributes)) return '';

        return $this->getAttributePropertySection('dates', $attributes);
    }

    /**
     * Returns the replacement for the default attributes placeholder
     *
     * @return string
     */
    protected function getDefaultsReplace()
    {
        if ( ! config('pxlcms.models.include_defaults')) return '';

        $attributes = $this->data['defaults'] ?: [];

        if ( ! count($attributes)) return '';

        // align assignment signs by longest attribute
        $longestLength = 0;

        foreach ($attributes as $attribute => $default) {

            if (strlen($attribute) > $longestLength) {
                $longestLength = strlen($attribute);
            }
        }

        $replace = $this->tab() . "protected \$attributes = [\n";

        foreach ($attributes as $attribute => $default) {

            $replace .= $this->tab(2)
                . "'" . str_pad($attribute . "'", $longestLength + 1)
                . " => " . $default . ",\n";
        }

        $replace .= $this->tab() . "];\n\n";

        return $replace;
    }


    // ------------------------------------------------------------------------------
    //      Helpers
    // ------------------------------------------------------------------------------

    /**
     * @param string $variable
     * @param array  $attributes
     * @return string
     */
    protected function getAttributePropertySection($variable, array $attributes)
    {
        $replace = $this->tab() . "protected \${$variable} = [\n";

        foreach ($attributes as $attribute) {
            $replace .= $this->tab(2) . "'" . $attribute . "',\n";
        }

        $replace .= $this->tab() . "];\n\n";

        return $replace;
    }

}
