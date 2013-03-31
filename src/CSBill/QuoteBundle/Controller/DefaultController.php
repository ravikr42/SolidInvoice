<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\QuoteBundle\Controller;

use CS\CoreBundle\Controller\Controller;
use CSBill\QuoteBundle\Form\Type\QuoteType;
use CSBill\QuoteBundle\Entity\Quote;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Action\DeleteMassAction;

use Symfony\Component\HttpFoundation\Response;
use CSBill\QuoteBundle\Grid\DataGrid;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $source = new Entity('CSBillQuoteBundle:Quote');

        // Get a Grid instance
        $grid = $this->get('grid');

        /*$request = $this->getRequest();

        // TODO : get better way of adding filters & search instead of defining it in the controller like this
        $filters = new Filters($this->getRequest());

        $filters->add('all_clients', null, true, array('active_class' => 'label label-info', 'default_class' => 'label'));

        // TODO : get status from database
        $statuses = new Status;
        foreach ($statuses->getStatusList() as $status) {
            $filters->add($status.'_clients', function(QB $qb) use ($status) {
                $alias = $qb->getRootAlias();

                $qb->join($alias.'.status', 's')
                ->andWhere('s.name = :status_name')
                ->setParameter('status_name', $status);
            }, false, array('active_class' => 'label label-' . $statuses->getStatusLabel($status), 'default_class' => 'label'));
        }

        $search = $this->getRequest()->get('search');

        $source->manipulateQuery(function(QB $qb) use ($search, $filters) {

            if ($filters->isFilterActive()) {
                $filter = $filters->getActiveFilter();
                $filter($qb);
            }

            if ($search) {
                $alias = $qb->getRootAlias();

                $qb->andWhere($alias.'.name LIKE :search')
                ->setParameter('search', "%{$search}%");
            }
        });*/

        // Attach the source to the grid
        $grid->setSource($source);

        /*$grid->getColumn('status.name')->manipulateRenderCell(function($value, Row $row, Router $router) use ($statuses) {
            return '<label class="label label-'.$statuses->getStatusLabel($value).'">'.$value.'</label>';
        })->setSafe(false);*/

        // Custom actions column in the wanted position
        $viewColumn = new ActionsColumn('info_column', $this->get('translator')->trans('Info'));
        $grid->addColumn($viewColumn, 100);

        $viewAction = new RowAction($this->get('translator')->trans('View'), '_clients_view');
        $viewAction->setColumn('info_column');
        $grid->addRowAction($viewAction);

        $editColumn = new ActionsColumn('edit_column', $this->get('translator')->trans('Edit'));
        $grid->addColumn($editColumn, 200);

        $editAction = new RowAction($this->get('translator')->trans('Edit'), '_clients_edit');
        $editAction->setColumn('edit_column');
        $grid->addRowAction($editAction);

        $grid->addMassAction(new DeleteMassAction());

        $grid->hideColumns(array('updated', 'deleted'));

        // Return the response of the grid to the template
        return $grid->getGridResponse('CSBillQuoteBundle:Default:index.html.twig');
    }

    public function createAction()
    {
        $request = $this->getRequest();

        $quote = new Quote;

        $form = $this->createForm(new QuoteType(), $quote);

        if ($request->getMethod() === 'POST') {

            $form->bind($request);

            if ($form->isValid()) {

                if($request->request->get('save') === 'draft') {
                    $quote->setDraft(1);
                }

                $em = $this->get('doctrine')->getManager();

                $this->flash($this->trans('Quote created successfully'), 'success');

                $em->persist($quote);
                $em->flush();

                // TODO : we need to send the quote to the client if it is not saved as a draft
                if($request->request->get('save') === 'send') {
                    $this->get('some.service.id')->sendQuote($quote);
                }

                return $this->redirect($this->generateUrl('_quotes_index'));
            }
        }

        return $this->render('CSBillQuoteBundle:Default:create.html.twig', array('form' => $form->createView()));
    }
}