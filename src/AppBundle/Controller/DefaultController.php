<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Form\Type\UploadType;
use AppBundle\Entity\Uploader;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $uploader = new Uploader();
        $form = $this->createForm(UploadType::class, $uploader);
        $form->handleRequest($request);
        $error = '';
        if ($form->isSubmitted() && $form->isValid()) {
            
            $excel = $this->get('phpexcel')->createPHPExcelObject($uploader->getExcel());
             $worksheet = $excel->getSheet(0);
             $pdf = $uploader->getPDF();
             $valid =  $this->processData($worksheet, $pdf);
            dump($valid);
            if (false !== $valid) {
                $count = 0;
                $countPDF = 0;
                foreach($valid as $email_client) {
                    //message, email, files
                    $this->sendEmail(
                        $uploader->getMessage(),
                        [ $email_client['email1'], $email_client['email2'] ], 
                        $email_client['pdf']
                    );
                    $countPDF = $countPDF + sizeof($email_client['pdf']);
                    $count++;
                }
                // replace this example code with whatever you need
                return $this->render('default/index.html.twig', [
                'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
                'form' => $form->createView(),
                'cant_email' => $count,
                'cant_pdf' => $countPDF,
                'data' => $valid 
                ]);
            }
            $error = 'El excel no contiene las 4 columnas obligatorias';
           
        }
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
            'form' => $form->createView(),
            'error_message' => $error
        ]);
    }

    private function processData($worksheet, $pdf)
    {
        $count = 0;
        $finalPDF = [];
        foreach ($worksheet->getRowIterator() as $row => $columns) {
            $cellIterator = $columns->getCellIterator();
            $columna = 0;
            $pdfs = [];
            $nit = '';
            $email = '';
            $cliente = '';
            foreach ($cellIterator as $cell) {
                $data = $cell->getCalculatedValue();
                 // nombre cliente
                if ($columna === 0 && $count !== 0 && !empty($data)){
                    $cliente = $data;
                }
                // NIT
                if ($columna === 1 && $count !== 0 && !empty($data) && !empty($pdf)){
                    $pdfs = $this->buscarPDF($data, $pdf);
                    $nit = $data;
                }
                //correo 1
                $pdfsWithEmail = [];
                if ($columna === 2 && $count !== 0) {
                    $email = $data;
                }
                //correo 2
                if ($columna === 3 && $count !== 0 && !empty($pdfs)) {
                    $pdfsWithEmail['cliente'] = $cliente;
                    $pdfsWithEmail['pdf'] = $pdfs;
                    $pdfsWithEmail['nit'] = strval($nit);
                    $pdfsWithEmail['email1'] = $email;
                    $pdfsWithEmail['email2'] = $data;
                    $finalPDF[] = $pdfsWithEmail;
                    $columna++;
                }
                ++$columna;
            }
            if ($columna < 4) {
                return false;
            }
            $columna = 0;
            ++$count;
        }
        return $finalPDF;
    }

    private function buscarPDF($nit, $pdfs)
    {
        $result = [];
        foreach($pdfs as $pdf) {
            $fileName = $pdf->getClientOriginalName();
            //convert number to string: strval
            //find if file name includes nits
            if (strpos($fileName, strval($nit)) !== false) {
                $result[] = $pdf;
            }
        }
        return $result;
    }

     /**
     * Función para enviar un correo.
     *
     * @param Usuario $enviado_a Nombre de la persona a la que se le envía el correo
     * @param array usuarios que no tienen horas aprobadas
     */
    private function sendEmail($body, $emailTo, $files)
    {
        $fromEmail = 'no-responder@email.chapilabs.com';

        $message = \Swift_Message::newInstance();
        foreach($files as $file) {
            $attachment = \Swift_Attachment::fromPath($file->getRealPath())->setFileName($file->getClientOriginalName());     
            // Attach it to the message
            $message->attach($attachment);
        }
       

      

        $subject = 'Retenciones ISR e IVA';

        $message
            ->setSubject($subject)
            ->setFrom([$fromEmail => 'SBA'])
            ->setTo($emailTo[0])
            ->setBody($this->renderView('email/email.html.twig', [ 'body' => ($body)]), 'text/html')
            ->setContentType('text/html')
        ;
        if ($emailTo[1] !== null) {
            $message->setBcc($emailTo[1]);
        }

        $this->get('mailer')->send($message);
    }
}
