<?php

namespace Webleit\ZohoSignApi\Actions;

use Webleit\ZohoSignApi\Models\Request;
use Webleit\ZohoSignApi\Models\Template;
use Webleit\ZohoSignApi\Modules\Requests;


/**
 * Class SignTemplate
 * @package Webleit\ZohoSignApi\Actions
 */
class SignTemplate
{
    /**
     * @var Template
     */
    protected $template;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $phone;

    /**
     * @var string
     */
    protected $countryCode;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $verificationType;

    /**
     * @var string
     */
    protected $verificationCode;

    /**
     * @var string
     */
    protected $role;

    /**
     * SignTemplate constructor.
     * @param Template $template
     */
    public function __construct (Template $template)
    {
        $this->template = $template;
    }

    /**
     * @param $role
     * @return $this
     */
    public function toRole($role)
    {
        $this->role = $role;
        return $this;
    }

    /**
     * @param $email
     * @return $this
     */
    public function toEmail ($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param $phone
     * @return $this
     */
    public function toPhone ($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @param $code
     * @return $this
     */
    public function toCountryCode($code)
    {
        $this->countryCode = $code;
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function toName ($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return $this
     */
    public function verifyViaEmail ()
    {
        $this->verificationType = 'EMAIL';
        return $this;
    }

    /**
     * @return Request
     * @throws \Webleit\ZohoSignApi\Exception\ApiError
     * @throws \Webleit\ZohoSignApi\Exception\GrantCodeNotSetException
     */
    public function send()
    {
        $actions = $this->template->actions;

        foreach ($actions as &$action) {
            if (!$this->role || $this->role === $action['role']) {
                unset($action['fields']);

                $action['recipient_name'] = $this->name;
                $action['recipient_email'] = $this->email;

                if ($this->verificationType) {
                    $action['verify_recipient'] = true;
                    $action['recipient_phonenumber'] = $this->phone;
                    $action['recipient_countrycode'] = $this->countryCode;
                }
            }
        }

        $client = $this->template->getModule()->getClient();

        $response = $client->call('templates/' . $this->template->getId() . '/createdocument', 'POST', [], [
            'templates' => [
                'actions' => $actions
            ]
        ]);

        $response = json_decode($response->getBody(), true);

        return new Request($response['requests'], new Requests($this->template->getModule()->getClient()));
    }
}