<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
/**
 * @ORM\Entity @ORM\Table(name="contratos")
 */
class Contrato {
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $codigo;
    /**
     * @ORM\Column(type="integer")
     */
    private $usuario;
    /**
     * @ORM\Column(type="integer")
     */
    private $contrato = 1;
    /**
     * @ORM\Column(type="integer")
     */
    private $personal = 0;
    /**
     * @ORM\Column(type="integer")
     */
    private $horario = 0;
    /**
     * @ORM\Column(type="date")
     */
    private $inicio;
   


    /**
     * Get the value of codigo
     */ 
    public function getCodigo()
    {
        return $this->codigo;
    }

    /**
     * Set the value of codigo
     *
     * @return  self
     */ 
    public function setCodigo($codigo)
    {
        $this->codigo = $codigo;

        return $this;
    }

    /**
     * Get the value of usuario
     */ 
    public function getUsuario()
    {
        return $this->usuario;
    }

    /**
     * Set the value of usuario
     *
     * @return  self
     */ 
    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;

        return $this;
    }

    /**
     * Get the value of contrato
     */ 
    public function getContrato()
    {
        return $this->contrato;
    }

    /**
     * Set the value of contrato
     *
     * @return  self
     */ 
    public function setContrato($contrato)
    {
        $this->contrato = $contrato;

        return $this;
    }

    public function getHorario() {
        return $this->horario;
    }

    public function setHorario($h) {
        $this->horario = $h;
        return $this;
    }

    /**
     * Get the value of inicio
     */ 
    public function getInicio()
    {
        return $this->inicio;
    }

    /**
     * Set the value of inicio
     *
     * @return  self
     */ 
    public function setInicio($inicio)
    {
        $this->inicio = $inicio;

        return $this;
    }

    /**
     * Get the value of personal
     */ 
    public function getPersonal()
    {
        return $this->personal;
    }

    /**
     * Set the value of personal
     *
     * @return  self
     */ 
    public function setPersonal($personal)
    {
        $this->personal = $personal;

        return $this;
    }
}
?>