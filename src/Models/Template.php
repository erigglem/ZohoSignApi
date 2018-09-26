<?php

namespace Webleit\ZohoSignApi\Models;
use Webleit\ZohoSignApi\Actions\SignTemplate;

/**
 * Class Template
 * @package Webleit\ZohoSignApi\Models
 *
 * @property-read array $actions
 * @property-read array $document_ids
 * @property-read string $template_id
 */
class Template extends Model
{
    /**
     * @return SignTemplate
     */
    public function sendForSigning()
    {
        return new SignTemplate($this);
    }
}