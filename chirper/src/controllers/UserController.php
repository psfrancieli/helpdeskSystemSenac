<?php

require_once __DIR__ . '/../repositories/UserRepository.php';

class UserController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public function getById(int $id): User
    {
        return $this->userRepository->listarUsuarioPorId($id);
    }
}
