<?php

namespace App\Entity\Fichar;
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
     * @return mixed
     */
    public function getRol(){
        return $this->rol;
    }
    /**
     * @param mixed $rol
     */
    public function setRol($rol){
        $this->rol = $rol;
    }

    public function getCodigo() {
        return $this->codigo;
    }
    public function setCorreo($correo) {
        $this->correo = $correo;
    }
	public function getCorreo() {
        return $this->correo;
    }
    public function setNombre($nombre) {
        $this->nombre = $nombre;
    }
	public function getNombre() {
        return $this->nombre;
    }
    public function setClave($clave) {
        $this->clave = $clave;
    }
	public function getClave() {
        return $this->clave;
    }
    public function setDNI($dni) {
        $this->dni = $dni;
    }
	public function getDNI() {
        return $this->dni;
    }

    public function setRecuperacion($recuperacion) {
        $this->recuperacion = $recuperacion;
    }
	public function getRecuperacion() {
        return $this->recuperacion;
    }
    public function setExpiracion($expiracion) {
        $this->expiracion = $expiracion;
    }
	public function getExpiracion() {
        return $this->expiracion;
    }
 	// =======================================================
	// Elementos necesarios para la autenticación
	// =======================================================
   public function getRoles()
    {
        return array('ROLE_USER');                  
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

	public function serialize(){
        return serialize(array(
            $this->codigo,
            $this->correo,
            $this->nombre,
            $this->clave,
            $this->dni,
            $this->rol,
            $this->recuperacion,
            $this->expiracion,
        ));
    }
	
    public function unserialize($serialized){
        list (
            $this->codigo,
            $this->correo,
            $this->nombre,
            $this->clave,
            $this->dni,
            $this->rol,
            $this->recuperacion,
            $this->expiracion,
            ) = unserialize($serialized);
    }

}