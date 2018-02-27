<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity
 */
class Uploader
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;


    /**
     * @Assert\File(
     *     maxSize = "5M",
     *     mimeTypes = {"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "aapplication/vnd.ms-excel.addin.macroEnabled.12"},
     *     mimeTypesMessage = "Solo se permiten Excel"
     * )
     */
    protected $excel;

    private $message;

    /**
     * @Assert\All({
     *     @Assert\File(
     *         maxSize = "100M",
     *         mimeTypes = {"application/pdf", "application/x-pdf"},
     *          mimeTypesMessage = "Solo se permiten PDF"
     *          )
     * })
     */
    protected $pdf;

    public function getPDF()
    {
        return $this->pdf;
    }

    public function getExcel()
    {
        return $this->excel;
    }

    public function setPDF($pdf)
    {
        $this->pdf = $pdf;
    }

    public function setExcel($excel)
    {
        $this->excel = $excel;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }



}