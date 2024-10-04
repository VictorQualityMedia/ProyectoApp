<?php

namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
/**
 * @ORM\Entity @ORM\Table(name="color")
 */
class Color
{
    /**
     * @ORM\Id 
     * @ORM\Column(type="integer")
     */
    private $id_color;
    /**
     * @ORM\Column(type="string")
     */
    private $color;

    public function getId() {
        return $this->id_color;
    }
    public function setId($id_color) {
       $this->id_color = $id_color;
    }
    public function getColor() {
        return $this->color;
    }
    public function setColor($color) {
        $this->color = $color;
    }
}