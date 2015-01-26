<?php
namespace Pixmicat\Pio;

/**
 * 協助設定 status 旗標的類別
 */
class FlagHelper
{
    /** @var string status raw value */
    private $status;

    public function __construct($status = '')
    {
        $this->setStatus($status);
    }

    private function setStatus($status = '')
    {
        $this->status = $status;
    }

    public function toString()
    {
        return $this->status;
    }

    public function get($flag)
    {
        $result = \preg_match('/_(' . $flag . '(\:(.*))*)_/U', $this->toString(), $match);
        return $result ? $match[1] : false;
    }

    public function exists($flag)
    {
        return $this->get($flag) !== false;
    }

    public function value($flag)
    {
        $wholeflag = $this->get($flag);
        $scount = \substr_count($wholeflag, ':');
        if ($scount > 0) {
            $wholeflag = \preg_replace('/^' . $flag . '\:/', '', $wholeflag);
            return $scount > 1
                ? \explode(':', $wholeflag)
                : $wholeflag;
        } else {
            return $wholeflag !== false;
        }
    }

    public function add($flag, $value = null)
    {
        return $this->update($flag, $value);
    }

    public function update($flag, $value = null)
    {
        if ($value === null) {
            $ifexist = $this->get($flag);
            if ($ifexist !== $flag) {
                $this->setStatus($this->toString() . "_${flag}_");
            }
        } else {
            if (\is_array($value)) {
                $value = $this->join($value);
            }

            // Array Flatten
            $ifexist = $this->get($flag);
            if ($ifexist !== $flag . ':' . $value) {
                if ($ifexist) {
                    // 已立flag，不同值
                    $this->setStatus($this->replace($ifexist, "$flag:$value"));
                } else {
                    // 無flag
                    $this->setStatus($this->toString() . "_$flag:${value}_");
                }
            }
        }
        return $this;
    }

    public function replace($from, $to)
    {
        return \str_replace("_${from}_", "_${to}_", $this->toString());
    }

    public function remove($flag)
    {
        $wholeflag = $this->get($flag);
        $this->setStatus(\str_replace("_${wholeflag}_", '', $this->toString()));
        return $this;
    }

    public function toggle($flag)
    {
        return $this->get($flag)
            ? $this->remove($flag)
            : $this->add($flag);
    }

    public function offsetValue($flag, $d = 0)
    {
        $v = intval($this->value($flag));
        return $this->update($flag, $v + $d);
    }

    public function plus($flag)
    {
        return $this->offsetValue($flag, 1);
    }

    public function minus($flag)
    {
        return $this->offsetValue($flag, -1);
    }

    public function join()
    {
        $arg = \func_get_args();
        $newval = array();
        foreach ($arg as $a) {
            if (\is_array($a)) {
                \array_push($newval, \implode(':', $a));
            } else {
                \array_push($newval, $a);
            }
        }
        return \implode(':', $newval);
    }

    public function __toString()
    {
        return \sprintf('%s {status = %s}', __CLASS__, $this->toString());
    }
}
