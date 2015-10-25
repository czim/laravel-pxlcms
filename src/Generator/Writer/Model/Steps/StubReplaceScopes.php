<?php
namespace Czim\PxlCms\Generator\Writer\Model\Steps;

use Czim\PxlCms\Generator\Writer\Model\CmsModelWriter;

class StubReplaceScopes extends AbstractProcessStep
{

    protected function process()
    {
        $this->stubPregReplace('# *{{SCOPES}}\n?#i', $this->getScopesReplace());
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
            $scopes['position_order'] = [
                'name'   => $this->prefixScopeToName(config('pxlcms.generator.models.scopes.position_order_method', 'ordered')),
                'return' => "\$query->orderBy(\$this->cmsPositionColumn)",
            ];
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
