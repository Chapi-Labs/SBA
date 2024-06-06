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
             $validClients =  $this->processData($worksheet, $pdf);
             $invalidPDFs = [];
             foreach($pdf as $individual_pdf) {
                $found = false;
                foreach($validClients as $client) {
                    if (in_array($individual_pdf, $client['pdf'])) {
                        $found = true;
                    }
                }
                if ($found === false) {
                    $invalidPDFs[] = $individual_pdf->getClientOriginalName();
                }
             }
            if ($validClients !== false) {
                $count = 0;
                $countPDF = 0;
                foreach($validClients as $email_client) {
                    //message, email, files
                    $this->sendEmail(
                        $uploader->getMessage(),
                        $email_client['emails'],
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
                    'data' => $validClients,
                    'invalid_data' => $invalidPDFs,
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
    /**
     * Function to group all pdfs with the emails of one client
     * @param  ExcelType $worksheet Depedency type
     * @param  Array of Files $pdf alld the PDFS uploaded
     * @return Array with valid clients and invalid
     */
    private function processData($worksheet, $pdf)
    {
        $count = 0;
        $clientsPDF = [];
        $rows = $worksheet->toArray();
        foreach ($rows as $row) {
            
            $columna = 0;
            $pdfs = [];
            $nit = '';
            $emails = [];
            $cliente = '';
            foreach ($row as $column) {
                $data = $column;
                // nombre cliente
                if ($columna === 0 && $count !== 0 && !empty($data)){
                    $cliente = $data;
                }
                // NIT
                if ($columna === 1 && $count !== 0 && !empty($data) && !empty($pdf)){
                    $pdfs = $this->buscarPDF($data, $pdf);
                    $nit = $data;
                }
                //correos
                $pdfsWithEmail = [];
                if ($columna >= 2 && $count !== 0) {
                    if (filter_var($data, FILTER_VALIDATE_EMAIL) !== false) {
                        $emails[] = $data;
                    }
                }
                //final column
                if ($columna === sizeof($row) - 1 && $count !== 0 && !empty($pdfs)) {
                    $pdfsWithEmail['cliente'] = $cliente;
                    $pdfsWithEmail['pdf'] = $pdfs;
                    $pdfsWithEmail['nit'] = strtoupper(strval($nit));
                    $pdfsWithEmail['emails'] = $emails;
                    $clientsPDF[] = $pdfsWithEmail;
                }
                ++$columna;
            }
            if ($columna < 4) {
                return false;
            }
            $columna = 0;
            ++$count;
        }
        return $clientsPDF;
    }
    /**
     * Find all the PDFS of a client
     * @param  string $nit  client identificator
     * @param  UploadFile[] $pdfs PDFS
     * @return UploadedFile[]
     */
    private function buscarPDF($nit, $pdfs)
    {
        $result = [];
        foreach($pdfs as $pdf) {
            $fileName = $pdf->getClientOriginalName();
            //convert number to string: strval
            //find if file name includes nits
            if (strpos(strtoupper($fileName), strtoupper(strval($nit))) !== false) {
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
            ->setTo($emailTo)
            ->setReplyTo('mpatal@diazreyes.com')
            ->setBody($this->renderView('email/email.html.twig', [ 'body' => ($body)]), 'text/html')
            ->setContentType('text/html')
        ;
        $headers = $message->getHeaders();
        $headers->addTextHeader('X-PM-Message-Stream', 'sba');

       $this->get('mailer')->send($message);
    }
}
