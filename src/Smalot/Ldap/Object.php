<?php

/**
 * @file
 *          PHP library which handle LDAP data. Can parse too LDIF file.
 *
 * @author  Sébastien MALOT <sebastien@malot.fr>
 * @license MIT
 * @url     <https://github.com/smalot/ldap>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Smalot\Ldap;

use Smalot\Ldap\Exception\NotFoundAttributeException;

/**
 * Class Object
 *
 * @package Smalot\Ldap
 */
class Object
{
    /**
     * @var string
     */
    protected $distinguishedName;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * @param string $dn
     * @param array  $attributes
     */
    public function __construct($dn = null, $attributes = array())
    {
        $this->distinguishedName = $dn;

        // Supports array of array or array of Attribute objects
        foreach ($attributes as $name => $attribute) {
            if (!$attribute instanceof Attribute) {
                $attribute = new Attribute($name, $attribute);
            }

            $this->set($attribute);
        }
    }

    /**
     * @return string
     */
    public function getDistinguishedName()
    {
        return $this->distinguishedName;
    }

    /**
     * @param string $dn
     *
     * @return $this
     */
    public function setDistinguishedName($dn)
    {
        $this->distinguishedName = $dn;

        return $this;
    }

    /**
     * @return string
     */
    public function getParentDN()
    {
        $parts = ldap_explode_dn($this->distinguishedName, 0);
        unset($parts['count']);
        unset($parts[0]);

        return implode(',', $parts);
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * @param string $name
     * @param bool   $create
     *
     * @return Attribute
     *
     * @throws NotFoundAttributeException
     */
    public function get($name, $create = true)
    {
        $name = strtolower($name);

        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        if ($create) {
            return ($this->attributes[$name] = new Attribute($name));
        }

        return new NotFoundAttributeException('Attribute not found');
    }

    /**
     * @param Attribute $attribute
     *
     * @return $this
     */
    public function set($attribute)
    {
        $this->attributes[strtolower($attribute->getName())] = $attribute;

        return $this;
    }

    /**
     * @param mixed $attribute
     *
     * @return $this
     */
    public function remove($attribute)
    {
        if ($attribute instanceof Attribute) {
            $name = strtolower($attribute->getName());
            unset($this->attributes[$name]);
        } else {
            unset($this->attributes[strtolower($attribute)]);
        }

        return $this;
    }

    /**
     * Build array as expected by ldap functions
     *
     * @param bool $keepEmpty
     *
     * @return array
     */
    public function getEntry($keepEmpty = true)
    {
        $entry = array();

        /** @var Attribute $attribute */
        foreach ($this->attributes as $name => $attribute) {
            $values = $attribute->getValues();

            if (count($values) > 1) {
                foreach ($values as $value) {
                    if ($value != '' || $keepEmpty) {
                        $entry[$name][] = $value;
                    }
                }
            } else {
                if ($values[0] != '' || $keepEmpty) {
                    $entry[$name] = $values[0];
                }
            }
        }

        return $entry;
    }
}
