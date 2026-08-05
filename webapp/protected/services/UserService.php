<?php

/**
 * Responsabilidad: todo lo que significa "crear" y "autenticar" un
 * usuario como concepto de negocio, no como fila de tabla. Depende de
 * UserRepositoryInterface (no de UserRepository concreto), por lo que
 * puede recibir cualquier implementacion que respete el contrato
 * (Dependency Inversion) — util, por ejemplo, para pruebas con un
 * repositorio falso en memoria.
 */
class UserService
{
    /** @var UserRepositoryInterface */
    private $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Logica de negocio: crear un usuario es mas que un INSERT. Hay
     * que decidir COMO se protege la contrasena (algoritmo de hash,
     * coste) antes de que el modelo la vea. El modelo (User::rules())
     * solo sabe validar la FORMA del dato (longitud, unicidad); no
     * sabe ni debe saber que existe un password en claro en algun
     * momento del proceso.
     *
     * @return User|false el usuario creado, o false si fallo la validacion/guardado
     */
    public function createUser($username, $email, $password)
    {
        $user = new User();
        $user->username = $username;
        $user->email = $email;
        $user->password_hash = password_hash($password, PASSWORD_DEFAULT);

        if (!$this->userRepository->save($user)) {
            return false;
        }

        return $user;
    }

    /**
     * Autenticar no es "buscar un usuario", es buscarlo Y verificar
     * que la contrasena en claro corresponde al hash guardado. Meter
     * esa verificacion en el repository violaria su responsabilidad
     * unica (acceso a datos); por eso vive aqui, en la capa de
     * negocio.
     *
     * @return User|false el usuario si las credenciales son correctas
     */
    public function authenticateUser($username, $password)
    {
        $user = $this->userRepository->findByUsername($username);

        if ($user === null) {
            return false;
        }

        if (!password_verify($password, $user->password_hash)) {
            return false;
        }

        return $user;
    }
}
