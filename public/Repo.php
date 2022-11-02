<?php

namespace App;

class Repo {
    public $all;
    public function __construct() {
        $registredUsersJson = explode(';', file_get_contents('registred-users.phtml'));
        $registredUsers = array_map(fn($user) => json_decode($user, true), $registredUsersJson);
        if (empty($registredUsers[count($registredUsers) - 1])) {
            array_pop($registredUsers);
        }
        $this->all = $registredUsers;
    }

    public function findByName($name) {
        $filtredUsers = array_values(array_filter($this->all, fn($user) => str_contains(strtolower($user['name']), strtolower($name))));
        return $filtredUsers;
    }

    public function findById($id) {
        $currentUser = array_values(array_filter($this->all, fn($user) => $user['id'] === $id));
        return $currentUser[0];
    }

    public function save($user) {
        $filtredUsers = array_filter($this->all, fn($thisUser) => $user['id'] !== $thisUser['id']);
        $updatingUser = array_filter($this->all, fn($thisUser) => $user['id'] === $thisUser['id']);
        if (!empty($updatingUser)) {
            file_put_contents('registred-users.phtml', '');
            foreach($filtredUsers as $newUser) {
                file_put_contents('registred-users.phtml', json_encode($newUser) . ";\n", FILE_APPEND);
            } 
        }
        $filtredUsers[] = $user;
        file_put_contents('registred-users.phtml', json_encode($user) . ";\n", FILE_APPEND);
    }

    public function delete($id) {
        $filtredUsers = array_filter($this->all, fn($thisUser) => $id !== $thisUser['id']);
        file_put_contents('registred-users.phtml', '');
        foreach($filtredUsers as $newUser) {
            file_put_contents('registred-users.phtml', json_encode($newUser) . ";\n", FILE_APPEND);
        }
    }
    
}

