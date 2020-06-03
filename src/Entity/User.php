<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity(fields="email", message="Email already taken")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class User implements UserInterface
{
    public function __construct()
    {
        $this->isActive = false;
    }

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = 2,
     *      max = 50,
     *      minMessage = "Your first name must be at least {{ limit }} characters long",
     *      maxMessage = "Your first name cannot be longer than {{ limit }} characters",
     *      allowEmptyString = false
     *     )
     */
    private $username;

    /**
     * @ORM\Column(type="date")
     * @Assert\NotBlank()
     * @Assert\DateTime
     *
     */
    private $birth;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=4096)
     */
    private $plainPassword;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotBlank()
     */
    private $isActive;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated_at;

    /**
     *
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $tokenRegistration;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getBirth() :\DateTime
    {
        return $this->birth;
    }

    public function setBirth(\DateTime $birth): self
    {
        $this->birth = $birth;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($password)
    {
        $this->plainPassword = $password;
    }

    public function setStatus(bool $isActive= false): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getStatus(){
        return $this->isActive;
    }

    public function getSalt()
    {
        // you *may* need a real salt depending on your encoder
        // see section on salt below
        return null;
    }

    public function getTokenRegistration(): ?string
    {
        return $this->tokenRegistration;
    }

    /**
     * @ORM\PrePersist
     */
    public function setTokenRegistration()
    {
        $this->tokenRegistration = md5(microtime(TRUE)*100000);
    }

    public function getRoles()
    {
        return array('ROLE_USER');
    }

    public function eraseCredentials(){

    }

}
