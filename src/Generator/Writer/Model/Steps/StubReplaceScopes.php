<?php
namespace Czim\PxlCms\Generator\Writer\Model\Steps;

use Czim\PxlCms\Generator\Writer\Model\CmsModelWriter;

class StubReplaceScopes extends AbstractProcessStep
{

    protected function process()
    {
        $this->stubPregReplace('# *{{SCOPES}}\n?#i', $this->getScopesReplace())
             ->stubPregReplace('# *{{CMSORDEREDCONFIG}}\n?#i', $this->getCmsOrderedConfigReplace());
    }


    /**
     * @return string
     */
    protected function getScopesReplace()
    {
        // if scopes are all global or disabled, there is nothing to add

        $scopes = [];

        if ($this->useScopeActive()) {
            $scopes['only_active'] = [
                'name'   => $this->prefixScopeToName(config('pxlcms.generator.models.scopes.only_active_method', 'active')),
                'return' => "\$query->where(\$this->cmsActiveColumn, true)",
            ];
        }

        if ($this->useScopePosition()) {

            if (count($this->data['ordered_by'])) {

                $orderParts = "";

                foreach ($this->data['ordered_by'] as $orderPart => $direction) {
                    $orderParts .= "->orderBy(\$this->getTable() . '."
                                 . $orderPart . "'"
                                 . (strtolower($direction) !== 'asc' ? ", 'desc'" : null)
                                 . ")";
                }

                $scopes['cms_order'] = [
                    'name'   => $this->prefixScopeToName(config('pxlcms.generator.models.scopes.position_order_method', 'ordered')),
                    'return' => "\$query" . $orderParts,
                ];

            } else {

                $scopes['position_order'] = [
                    'name'   => $this->prefixScopeToName(config('pxlcms.generator.models.scopes.position_order_method', 'ordered')),
                    'return' => "\$query->orderBy(\$this->getTable() . '.' . \$this->cmsPositionColumn)",
                ];
            }
        }


        if ( ! count($scopes)) return '';


        $replace = "\n"
                 . $this->tab() . "/*\n"
                 . $this->tab() . " * Scopes\n"
                 . $this->tab() . " */\n\n";


        foreach ($scopes as $scope) {

            $replace .= $this->tab() . "public function {$scope['name']}(\$query)\n"
                      . $this->tab() . "{\n"
                      . $this->tab(2) . "return {$scope['return']};\n"
                      . $this->tab() . "}\n"
                      . "\n";
        }

        return $replace;
    }

    /**
     * Returns the replace required depending on whether the CMS module has
     * automatic sorting for defined columns
     *
     * @return string
     */
    protected function getCmsOrderedConfigReplace()
    {
        $orderColumns = $this->data['ordered_by'] ?: [];

        if ( ! count($orderColumns)) return '';

        // we only need to set a config if the scope method is global
        if (config('pxlcms.generator.models.scopes.position_order') !== CmsModelWriter::SCOPE_GLOBAL) return '';


        // align assignment signs by longest attribute
        $longestLength = 0;

        foreach ($orderColumns as $attribute => $direction) {

            if (strlen($attribute) > $longestLength) {
                $longestLength = strlen($attribute);
            }
        }

        $replace = $this->tab() . "protected \$cmsOrderBy = [\n";

        foreach ($orderColumns as $attribute => $direction) {

            $replace .= $this->tab(2)
                . "'" . str_pad($attribute . "'", $longestLength + 1)
                . " => '" . $direction . "',\n";
        }

        $replace .= $this->tab() . "];\n\n";

        return $replace;
    }

    /**
     * Prefixes the name with scope, for the scope method name
     *
     * @param string $name
     * @return mixed
     */
    protected function prefixScopeToName($name)
    {
        return camel_case('scope' . ucfirst($name));
    }

    /**
     * Returns whether we're using a global scope for active
     *
     * @return bool
     */
    protected function useScopeActive()
    {
        if (is_null($this->data['scope_active'])) {
            return config('pxlcms.generator.models.scopes.only_active') === CmsModelWriter::SCOPE_METHOD;
        }

        return (bool) $this->data['scope_active'];
    }

    /**
     * Returns whether we're using a global scope for position
     *
     * @return bool
     */
    protected function useScopePosition()
    {
        if (is_null($this->data['scope_position'])) {
            return config('pxlcms.generator.models.scopes.position_order') === CmsModelWriter::SCOPE_METHOD;
        }

        return (bool) $this->data['scope_position'];
    }
}
