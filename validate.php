<?php
namespace CASHER\LIB;

trait Validate
{

    private $_regexPatterns = [
        'num'           => '/^[0-9]+(?:\.[0-9]+)?$/',
        'int'           => '/^[0-9]+$/',
        'float'         => '/^[0-9]+\.[0-9]+$/',
        'alpha'         => '/^[a-zA-Z\p{Arabic} ]+$/u',
        'alphanum'      => '/^[a-zA-Z\p{Arabic}0-9 ,.|]+$/u',
        'validDate'     => '/^[1-2][0-9][0-9][0-9]-(?:(?:0[1-9])|(?:1[0-2]))-(?:(?:0[1-9])|(?:(?:1|2)[0-9])|(?:3[0-1]))$/',
        'email'         => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
        'url'           => '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/'
    ];
    private function req ($value)
    {
        return '' != $value || !empty($value);
    }

    private function num($value)
    {
        return (bool) preg_match($this->_regexPatterns['num'], $value);
    }

    private function int($value)
    {
        return (bool) preg_match($this->_regexPatterns['int'], $value);
    }

    private function float($value)
    {
        return (bool) preg_match($this->_regexPatterns['float'], $value);
    }

    private function alpha($value)
    {
        return (bool) preg_match($this->_regexPatterns['alpha'], $value);
    }

    private function alphanum($value)
    {
        return (bool) preg_match($this->_regexPatterns['alphanum'], $value);
    }

    private function eq($value, $matchAgainst)
    {
        return $value == $matchAgainst;
    }

    private function eq_field($value, $otherFieldValue)
    {
        return $value == $otherFieldValue;
    }

    private function lt($value, $matchAgainst)
    {
        if(is_string($value)) {
            return mb_strlen($value) < $matchAgainst;
        } elseif (is_numeric($value)) {
            return $value < $matchAgainst;
        }
    }

    private function gt($value, $matchAgainst)
    {
        if(is_string($value)) {
            return mb_strlen($value) > $matchAgainst;
        } elseif (is_numeric($value)) {
            return $value > $matchAgainst;
        }
    }

    private function min($value, $min)
    {
        if(is_string($value)) {
            return mb_strlen($value) >= $min;
        } elseif (is_numeric($value)) {
            return $value >= $min;
        }
    }

    private function max($value, $max)
    {
        if(is_string($value)) {
            return mb_strlen($value) <= $max;
        } elseif (is_numeric($value)) {
            return $value <= $max;
        }
    }

    private function between($value, $min, $max)
    {
        if(is_string($value)) {
            return mb_strlen($value) >= $min && mb_strlen($value) <= $max;
        } elseif (is_numeric($value)) {
            return $value >= $min && $value <= $max;
        }
    }

    private function floatlike($value, $beforeDP, $afterDP)
    {
        if(!$this->float($value))
        {
            return false;
        }
        $pattern = '/^[0-9]{' . $beforeDP . '}\.[0-9]{' . $afterDP . '}$/';
        return (bool) preg_match($pattern, $value);
    }

    private function vdate($value)
    {
        return (bool) preg_match($this->_regexPatterns['validDate'], $value);
    }

    private function email($value)
    {
        return (bool) preg_match($this->_regexPatterns['email'], $value);
    }

    private function url($value)
    {
        return (bool) preg_match($this->_regexPatterns['url'], $value);
    }

    public function isValid($roles, $inputType) {
        $errors = [];
        if(!empty($roles)) {
            foreach ($roles as $fieldName => $validationRoles) {
                $value = isset($inputType[$fieldName]) ? $inputType[$fieldName] : '';
                $validationRoles = explode('|', $validationRoles);
                foreach ($validationRoles as $validationRole) {
                    if(array_key_exists($fieldName, $errors))
                        continue;
                    if(preg_match_all('/(min)\((\d+)\)/', $validationRole, $m)) {
                        if($this->min($value, $m[2][0]) === false) {
                            $this->messenger->add(
                                $this->language->feedKey($m[1][0] .'Error', [$this->language->get($fieldName), $m[2][0]]),
                                messenger::APP_MESSAGE_ERROR
                            );
                            $errors[$fieldName] = true;
                        }
                    } elseif (preg_match_all('/(max)\((\d+)\)/', $validationRole, $m)) {
                        if($this->max($value, $m[2][0]) === false) {
                            $this->messenger->add(
                                $this->language->feedKey($m[1][0] .'Error', [$this->language->get($fieldName), $m[2][0]]),
                                messenger::APP_MESSAGE_ERROR
                            );
                            $errors[$fieldName] = true;
                        }
                    } elseif(preg_match_all('/(lt)\((\d+)\)/', $validationRole, $m)) {
                        if($this->lt($value, $m[2][0]) === false) {
                            $this->messenger->add(
                                $this->language->feedKey($m[1][0] .'Error', [$this->language->get($fieldName), $m[2][0]]),
                                messenger::APP_MESSAGE_ERROR
                            );
                            $errors[$fieldName] = true;
                        }
                    } elseif(preg_match_all('/(gt)\((\d+)\)/', $validationRole, $m)) {
                        if($this->gt($value, $m[2][0]) === false) {
                            $this->messenger->add(
                                $this->language->feedKey($m[1][0] .'Error', [$this->language->get($fieldName), $m[2][0]]),
                                messenger::APP_MESSAGE_ERROR
                            );
                            $errors[$fieldName] = true;
                        }
                    } elseif(preg_match_all('/(between)\((\d+),(\d+)\)/', $validationRole, $m)) {
                        if($this->between($value, $m[2][0], $m[3][0]) === false) {
                            $this->messenger->add(
                                $this->language->feedKey($m[1][0] . 'Error', [$this->language->get($fieldName), $m[2][0], $m[3][0]]),
                                messenger::APP_MESSAGE_ERROR
                            );
                            $errors[$fieldName] = true;
                        }
                    } elseif(preg_match_all('/(floatlike)\((\d+),(\d+)\)/', $validationRole, $m)) {
                        if($this->floatlike($value, $m[2][0], $m[3][0]) === false) {
                            $this->messenger->add(
                                $this->language->feedKey($m[1][0] .'Error', [$this->language->get($fieldName), $m[2][0], $m[3][0]]),
                                messenger::APP_MESSAGE_ERROR
                            );
                            $errors[$fieldName] = true;
                        }
                    } elseif(preg_match_all('/(eq)\((\w+)\)/', $validationRole, $m)) {
                        if($this->eq($value, $m[2][0]) === false) {
                            $this->messenger->add(
                                $this->language->feedKey($m[1][0] .'Error', [$this->language->get($fieldName), $m[2][0]]),
                                messenger::APP_MESSAGE_ERROR
                            );
                            $errors[$fieldName] = true;
                        }
                    } elseif(preg_match_all('/(eq_field)\((\w+)\)/', $validationRole, $m)) {
                        $otherFieldValue = $inputType[$m[2][0]];
                        if($this->eq_field($value, $otherFieldValue) === false) {
                            $this->messenger->add(
                                $this->language->feedKey($m[1][0] .'Error', [$this->language->get($fieldName), $this->language->get($m[2][0])]),
                                messenger::APP_MESSAGE_ERROR
                            );
                            $errors[$fieldName] = true;
                        }
                    } else {
                        if($this->$validationRole($value) === false) {
                            $this->messenger->add(
                                $this->language->feedKey($validationRole .'Error', [$this->language->get($fieldName)]),
                                messenger::APP_MESSAGE_ERROR
                            );
                            $errors[$fieldName] = true;
                        }
                    }
                }
            }
        }
        return empty($errors) ? true : false;
    }



}
