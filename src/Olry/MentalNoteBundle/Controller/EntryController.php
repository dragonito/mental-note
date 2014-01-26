<?php

namespace Olry\MentalNoteBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use Olry\MentalNoteBundle\Entity\Entry;
use Olry\MentalNoteBundle\Form\Type\EntryType;
use Olry\MentalNoteBundle\Thumbnail\ThumbnailService;

class EntryController extends AbstractBaseController
{

    private function processForm($form, Entry $entry, $request)
    {
        $form->handleRequest($request);

        if ($form->isValid()) {
            $entry->setUser($this->getUser());

            $this->getEm()->persist($entry);
            $this->getEm()->flush();

            return true;
        }

        return false;
    }


    /**
     * @Route("/entry/{id}/toggle_pending.json",name="entry_toggle_pending")
     */
    public function togglePending(Entry $entry)
    {
        $entry->setPending(!$entry->getPending());
        $this->getEm()->flush();

        $filter = (array) $this->getRequest()->get('filter', array());

        return $this->redirect($this->generateUrl('homepage', array('filter' => $filter)));
    }

    /**
     * @Route("/thumbnails/{id}_{width}x{height}.png",name="entry_thumbnail")
     */
    public function thumbnailAction(Entry $entry, $width, $height)
    {
        $documentRoot = $this->container->getParameter('kernel.root_dir') . '/../web';
        $cacheDir     = $this->container->getParameter('kernel.cache_dir') . '/thumbnails';
        $route        = $this->generateUrl('entry_thumbnail', array('id' => $entry->getId(), 'width' => $width, 'height' => $height));

        $pathNew = sprintf('%s/thumbnails/%d_%dx%d.png', $documentRoot, $entry->getId(), $width, $height);

        // for dev mode
        if (file_exists($pathNew)) {
            $response = new BinaryFileResponse($pathNew);
            $this->get('logger')->error($entry->getId() . ':: file already exists, controller should not be executed');

            return $response;
        }

        // legacy code for old thumbnails
        $pathOld = sprintf('%s/thumbnails/%dx%d/%s.png', $documentRoot, $width, $height, md5($entry->getUrl()));
        if (file_exists($pathOld)) {
            $this->get('logger')->error($entry->getId() . ':: old file found, renaming');
            rename($pathOld, $pathNew);

            return $this->redirect($route);
        }

        try {
            $thumbnailService = new ThumbnailService($documentRoot, $cacheDir, 'thumbnails/{name}_{width}x{height}.png');
            $thumbnail        = $thumbnailService->generate($entry->getUrl(), $width, $height, $entry->getId());

            return $this->redirect($route);
        } catch (\Exception $e) {
            $this->get('logger')->error('Exception: ' . $e->getMessage());

            return $this->redirect("http://placehold.it/${width}x${height}", 301);
        }
    }

    /**
     * @Route("/entry/create.html",name="entry_create")
     * @Template()
     */
    public function createAction()
    {
        $request = $this->getRequest();
        $entry   = new Entry();
        $form    = $this->createForm(new EntryType($this->getDoctrine()->getManager()), $entry);

        if ($request->getMethod() == 'POST') {
            if ($this->processForm($form, $entry, $request)){
                return new Response('created', 201);
            }
        }

        return array('form' => $form->createView());
    }

    /**
     * @Route("/entry/{id}/edit.html",name="entry_edit")
     * @Template()
     */
    public function editAction(Entry $entry)
    {
        $request = $this->getRequest();
        $form    = $this->createForm(new EntryType($this->getDoctrine()->getManager()), $entry);

        if ($request->getMethod() == 'POST') {
            if ($this->processForm($form, $entry, $request)){
                return new Response('changed', 201);
            }
        }

        return array(
            'form'  => $form->createView(),
            'entry' => $entry,
        );
    }

    /**
     * @Route("/entry/{id}/delete.html",name="entry_delete")
     * @Template()
     */
    public function deleteAction(Entry $entry)
    {
        $request = $this->getRequest();
        $filter  = (array) $request->get('filter', array());
        $form    = $this->createFormBuilder($entry)->getForm();

        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $this->getEm()->remove($entry);
                $this->getEm()->flush();

                return $this->redirect($this->generateUrl('homepage', array('filter' => $filter)));
            }
        }

        return array(
            'form'   => $form->createView(),
            'entry'  => $entry,
            'filter' => $filter,
        );

    }

    /**
     * @Route("/entry/{id}/visit",name="entry_visit")
     * @Method("POST")
     */
    public function visitAction(Entry $entry)
    {
        $entry->addVisit();
        $this->getEm()->flush();

        return new Response('', 200);
    }

}


