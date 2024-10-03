<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity @ORM\Table(name="usuarios")
 */
class Usuario implements UserInterface, \Serializable
{
    /**
     * @ORM\Id 
     * @ORM\GeneratedValue 
     * @ORM\Column(type="integer", name = "codigo")
     */
    private $codigo;
    /**
     * @ORM\Column(type="string", name = "correo")
     */
    private $correo;
    /**
     * @ORM\Column(type="string", name = "nombre")
     */
    private $nombre;
    /**
     * @ORM\Column(type="string", name = "clave")
     */
    private $clave;
    /**
     * @ORM\Column(type="string", name = "dni")
     */
    private $dni;
    /**
     * @ORM\Column(type="integer", name = "rol")
     */
    private $rol;
    /**
     * @ORM\Column(type="integer", name = "recuperacion")
     */
    private $recuperacion;
    /**
     * @ORM\Column(type="datetimetz", name = "expiracion_rec")
     */
    private $expiracion;
    /**
     * @ORM\Column(type="string", name = "zona")
     */
    private $zona = 'Europe/Madrid';
    /**
     * @ORM\OneToOne(targetEntity="Departamentos")
     * @ORM\JoinColumn(name="departamento", referencedColumnName="codigo")
     */
    private $departamento;

    /**
     * @return mixed
     */
    public function getRol()
    {
        return $this->rol;
    }
    /**
     * @param mixed $rol
     */
    public function setRol($rol)
    {
        $this->rol = $rol;
    }

    public function getCodigo()
    {
        return $this->codigo;
    }
    public function setCorreo($correo)
    {
        $this->correo = $correo;
    }
    public function getCorreo()
    {
        return $this->correo;
    }
    public function setNombre($nombre)
    {
        $this->nombre = $nombre;
    }
    public function getNombre()
    {
        return $this->nombre;
    }
    public function setClave($clave)
    {
        $this->clave = $clave;
    }
    public function getClave()
    {
        return $this->clave;
    }
    public function setDNI($dni)
    {
        $this->dni = $dni;
    }
    public function getDNI()
    {
        return $this->dni;
    }
    public function setDepartamento($departamento)
    {
        $this->departamento = $departamento;
    }
    public function getDepartamento()
    {
        return $this->departamento;
    }
    public function setRecuperacion($recuperacion)
    {
        $this->recuperacion = $recuperacion;
    }
    public function getRecuperacion()
    {
        return $this->recuperacion;
    }
    public function setExpiracion($expiracion)
    {
        $this->expiracion = $expiracion;
    }
    public function getExpiracion()
    {
        return $this->expiracion;
    }
    // =======================================================
    // Elementos necesarios para la autenticación
    // =======================================================
    public function getRoles()
    {
        switch ($this->rol) {
            case 1:
                # code...
                return array('ROLE_ADMIN');
                break;
            case 2:
                # code...
                return array('ROLE_SUPERADMIN');
                break;
            default:
                # code...
                return array('ROLE_USER');
                break;
        }
    }

    public function getUserIdentifier()
    {
        return $this->getCorreo();
    }

    public function getPassword()
    {
        return $this->getClave();
    }

    public function getUsername()
    {
        return $this->getCorreo();
    }

    public function eraseCredentials()
    {
        return;
    }

    public function getSalt()
    {
        return;
    }

    public function serialize()
    {
        return serialize(array(
            $this->codigo,
            $this->correo,
            $this->nombre,
            $this->clave,
            $this->dni,
            $this->rol,
            $this->zona
        ));
    }

    public function unserialize($serialized)
    {
        list(
            $this->codigo,
            $this->correo,
            $this->nombre,
            $this->clave,
            $this->dni,
            $this->rol,
            $this->zona
        ) = unserialize($serialized);
    }

    /**
     * Get the value of zona
     */ 
    public function getZona()
    {
        return $this->zona;
    }

    /**
     * Set the value of zona
     *
     * @return  self
     */ 
    public function setZona($zona)
    {
        $this->zona = $zona;

        return $this;
    }
}
