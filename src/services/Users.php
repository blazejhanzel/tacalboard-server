<?php
require_once __DIR__ . '/../DatabaseProvider.php';
require_once __DIR__ . '/../dao/User.php';
require_once __DIR__ . '/../datatypes/ReturnCodes.php';
require_once __DIR__ . '/../datatypes/Service.php';


class Users extends Service {

    protected function doDelete(): ?ReturnCode {
        if ($this->isArg('/authorize')) {
            if (isset($_SESSION['user_id'])) {
                unset($_SESSION['user_id']);
                session_destroy();
                return new ReturnCode\NoContent();
            } else {
                return new ReturnCode\AuthorizationNotLogged();
            }
        }
        return null;
    }

    protected function doGet(): ?ReturnCode {
        if ($this->isArg('/authorize')) {
            if (isset($_SESSION['user_id'])) {
                echo json_encode(array(
                    'id' => (int) $_SESSION['user_id']
                ));
                return new ReturnCode\OK();
            } else {
                return new ReturnCode\AuthorizationNotLogged();
            }
        } else if ($this->isArg('/*/confirm/*')) {
            $user = User::getById($this->args[0]);
            if (!is_null($user)) {
                if (strcmp($user->getConfirmationKey(), $this->args[2]) !== 0) {
                    return new ReturnCode\DataNotEqual();
                }
            } else {
                return new ReturnCode\NotFound();
            }
        }
        return null;
    }

    protected function doPost(): ?ReturnCode {
        $json = User::createFromJson(file_get_contents('php://input'));
        if (!is_null($json)) {
            if ($this->isArg('/')) {
                if (is_null(User::getByEmail($json->getEmail()))) {
                    $user = User::create($json->getEmail(), $json->getPassword());
                    if (User::insert($user)) {
                        // TODO: Sending confirmation key on email
                        echo json_encode(array(
                            'key' => $json->getConfirmationKey()
                        ));
                        return new ReturnCode\OK();
                    } else {
                        return new ReturnCode\DatabaseQueryError();
                    }
                } else {
                    echo new ReturnCode\UniqueAlreadyUsed();
                }
            } else if ($this->isArg('/authorize')) {
                $user = User::getByEmail($json->getEmail());
                if (!is_null($user)) {
                    if (strcmp($user->getPassword(), $json->getPassword()) === 0) {
                        $_SESSION['user_id'] = $user->getId();
                        return new ReturnCode\OK();
                    } else {
                        return new ReturnCode\DataNotEqual();
                    }
                } else {
                    return new ReturnCode\NotFound();
                }
            }
        } else {
            return new ReturnCode\InsufficientContent();
        }
        return null;
    }

    protected function doPut(): ?ReturnCode {
        if ($this->isArg('/')) {
            if (isset($_SESSION['user_id'])) {
                $user = User::createFromJson(file_get_contents('php://input'));
                if (!is_null($user)) {
                    if ($user->getId() == $_SESSION['user_id']) {
                        if (!is_null(User::getById($user->getId()))) {
                            if (User::insert($user)) {
                                return new ReturnCode\OK();
                            } else {
                                return new ReturnCode\DatabaseQueryError();
                            }
                        } else {
                            return new ReturnCode\NotFound();
                        }
                    } else {
                        return new ReturnCode\AuthorizationRejected();
                    }
                } else {
                    return new ReturnCode\InsufficientContent();
                }
            } else {
                return new ReturnCode\AuthorizationNotLogged();
            }
        }
        return null;
    }

    protected function doPatch(): ?ReturnCode {
        parent::doPatch(); // TODO: Change the autogenerated stub
    }
}
