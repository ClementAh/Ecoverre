<?php
/**
 * Created by PhpStorm.
 * User: gauthierbosson
 * Date: 08/03/2019
 * Time: 10:00
 */

namespace App\Admin;


use App\Entity\Message;
use App\Entity\Users;
use Doctrine\ORM\EntityRepository;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\SenderToUsersTransformer;

class MessageReferentAdmin extends AbstractAdmin
{
    private $transformer;

    public function __construct(string $code, string $class, string $baseControllerName, SenderToUsersTransformer $transformer)
    {
        parent::__construct($code, $class, $baseControllerName);
        $this->transformer = $transformer;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();

        $formMapper->add('receiver', EntityType::class, [
            'label' => 'À',
            'class' => Users::class,
            'query_builder' => function(EntityRepository $er) {
                $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();
                return $er->createQueryBuilder('user')
                    ->where('user.city = :city')
                    ->andWhere('user.email != :email')
                    ->setParameter('city', $user->getCity())
                    ->setParameter('email', $user->getEmail());
            },
            'choice_label' => 'email'
        ]);
        $formMapper->add('object', TextType::class, [
            'label' => 'Sujet'
        ]);
        $formMapper->add('content', TextareaType::class, [
            'label' => 'Message',
            'attr' => ['rows' => 20]
        ]);
        $formMapper->add('sender', HiddenType::class,[
            'data' => $user->getId()
        ])
        ->get('sender')
        ->addModelTransformer($this->transformer);
        $formMapper->add('status', HiddenType::class, [
            'data' => 0
        ]);

    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('object');
        $datagridMapper->add('date');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper->addIdentifier('object');
        $listMapper->add('date');
    }

    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->tab('General') // the tab call is optional
            ->with('Message', [
                'class'       => 'col-md-8',
                'box_class'   => 'box box-solid box-success',
                'description' => 'Votre message',
            ])
            ->add('object')
            ->add('content')
            ->end()
            ->end()
        ;
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        // to remove a single route
        $collection->remove('edit');
    }

    protected function configureBatchActions($actions)
    {
        return parent::configureBatchActions($actions); // TODO: Change the autogenerated stub


    }

    public function toString($object)
    {
        return $object instanceof Message
            ? $object->getObject()
            : 'Message';
    }

    public function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setDefaults([
            'data_class'=>Users::class
        ]);
    }

    public function prePersist($object)
    {
        parent::prePersist($object); // TODO: Change the autogenerated stub

        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone('Europe/Paris'));
        $date->format('Y-m-d H:i:s (e)');
        $object->setDate($date);
    }

    public function postPersist($object)
    {
        parent::postPersist($object); // TODO: Change the autogenerated stub


    }

}