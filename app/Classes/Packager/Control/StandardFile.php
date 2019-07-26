<?php
namespace App\Classes\Packager\Control;

class StandardFile
    implements \ArrayAccess
{
    private $_keys = array(
        'Package' => false,
        'Version' => '0.1',
        "Section" => "web",
        "Priority" => "optional",
        "Architecture" => "all",
        "Essential" => "no",
        "Depends" => false,
        "Pre-Depends" => false,
        "Recommends" => false,
        "Suggests" => false,
        "Installed-Size" => 1024,
        "Maintainer" => "name <email>",
        "Conflicts" => false,
        "Replaces" => false,
        "Provides" => "your-company",
        "Description" => "Your description"
    );

    public function setPackageName($name)
    {
        return $this->_setProperty("Package", $name);
    }

    public function setVersion($version)
    {
        return $this->_setProperty("Version", $version);
    }

    public function setSection($section)
    {
        return $this->_setProperty("Section", $section);
    }

    public function setPriority($priority)
    {
        return $this->_setProperty("Priority", $priority);
    }

    public function setArchitecture($arch)
    {
        return $this->_setProperty("Architecture", $arch);
    }

    public function setEssential($essential)
    {
        return $this->_setProperty("Essential", $essential);
    }

    public function setDepends($depends)
    {
        return $this->_setProperty("Depends", $this->_transformList($depends));
    }

    public function setPreDepends($depends)
    {
        return $this->_setProperty("Pre-Depends", $this->_transformList($depends));
    }

    public function setRecommends($depends)
    {
        return $this->_setProperty("Recommends", $this->_transformList($depends));
    }

    public function setSuggests($depends)
    {
        return $this->_setProperty("Suggests", $this->_transformList($depends));
    }

    public function setInstalledSize($size)
    {
        return $this->_setProperty("Installed-Size", $size);
    }

    public function setMaintainer($maintainer, $email = false)
    {
        $email = ($email) ? $email : "---";
        return $this->_setProperty("Maintainer", "{$maintainer} <{$email}>");
    }

    public function setConflicts($conflicts)
    {
        return $this->_setProperty("Conflicts", $this->_transformList($conflicts));
    }

    public function setReplaces($replaces)
    {
        return $this->_setProperty("Replaces", $this->_transformList($replaces));
    }

    public function setProvides($provides)
    {
        return $this->_setProperty("Provides", $this->_transformList($provides));
    }

    public function setDescription($description)
    {
        return $this->_setProperty("Description", $description);
    }

    private function _transformList($depends)
    {
        if (is_array($depends)) {
            $depends = implode(", ", $depends);
        } else {
            $depends = $depends;
        }

        return $depends;
    }

    private function _setProperty($key, $value)
    {
        $this[$key] = $value;
        return $this;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_keys);
    }

    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->_keys[$offset];
        } else {
            return null;
        }
    }

    public function offsetSet($offset, $value)
    {
        if (!$this->offsetExists($offset)) {
            throw new \InvalidArgumentException("Invalid property '{$offset}' for this control file.");
        }
        $this->_keys[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->_keys[$offset]);
        }
    }

    public function __toString()
    {
        $control = '';
        foreach ($this->_keys as $key => $value) {
            if ($value) {
                $control .= "{$key}: {$value}" . PHP_EOL;
            }
        }

        return $control;
    }
}
