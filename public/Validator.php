<?php

namespace App;

class Validator {
    public function validate ($user) : array
    {
        $errors = [];
        if (empty($user['name'])) {
            $errors['name'] = "Can't be blank";
        }
        if (empty($user['email'])) {
            $errors['email'] = "Can't be blank";
        }
        if (empty($user['password'])) {
            $errors['password'] = "Can't be blank";
        }
        if (empty($user['city'])) {
            $errors['city'] = "Can't be blank";
        }
        return $errors;
    }

    public function updatingValidate ($user) : array
    {
        $errors = [];
        if (empty($user['name'])) {
            $errors['name'] = "Can't be blank";
        }
        if (empty($user['email'])) {
            $errors['email'] = "Can't be blank";
        }
        return $errors;
    }
}