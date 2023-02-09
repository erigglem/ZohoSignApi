<?php
namespace Webleit\ZohoSignApi\Models;

use Doctrine\Inflector\InflectorFactory;
use Tightenco\Collect\Contracts\Support\Arrayable;
use Webleit\ZohoSignApi\Contracts\Module;

/**
 * Class Model
 * @package Webleit\ZohoSignApi\Models
 */
abstract class Model implements \JsonSerializable, Arrayable
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var Module
     */
    protected $module;

    /**
     * Model constructor.
     * @param array $data
     * @param  Module   $module
     */
    public function __construct($data = [], Module $module)
    {
        $this->data = $data;
        $this->module = $module;
    }

    /**
     * @return Module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param $name
     * @return mixed
     */
    function __get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
    }

    /**
     * @param $name
     * @param $value
     */
    function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    function __call($name, $arguments)
    {
        // add "id" as a parameter
        array_unshift($arguments, $this->getId());

        if (method_exists($this->module, $name)) {
            return call_user_func_array([$this->module, $name], $arguments);
        }
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->getData();
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * is a new object?
     * @return bool
     */
    public function isNew()
    {
        return !$this->getId();
    }

    /**
     * Get the id of the object
     * @return bool|string
     */
    public function getId()
    {
        $key = $this->getKeyName();
        return $this->$key ? $this->$key : false;
    }

    /**
     * Get the name of the primary key
     */
    public function getKeyName()
    {
        return strtolower($this->getName() . '_id');
    }

    /**
     * @return string
     */
    public function getName()
    {
        $inflector = InflectorFactory::create()->build();
        return $inflector->singularize(strtolower((new \ReflectionClass($this))->getShortName()));
    }
}
