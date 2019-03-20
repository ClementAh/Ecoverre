<?php
/**
 * Created by PhpStorm.
 * User: joffrey
 * Date: 2019-03-11
 * Time: 14:34
 */

namespace App\Admin;


use App\Entity\Trashs;
use App\Repository\TrashRepository;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AdminTrashsAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();


        if($user->getCity()=== null){
            $formMapper->add('city', TextType::class, [
                'label' => 'Ville'

            ]);
        } else {
            $formMapper->add('city', HiddenType::class, [
                'data' => $user->getCity()

            ]);
        }


        $formMapper->add('address', TextType::class, [
            'label' => 'Adresse'
        ]);
        $formMapper->add('reference', TextType::class, [
            'label' => 'Référence'
        ]);
        $formMapper->add('capacityMax', TextType::class, [
            'label' => 'Capacité maximum'
        ]);
        $formMapper->add('actualCapacity',TextType::class,[
            'required'=>false,
            'label'=>'Nombre de bouteilles à l\'intérieur'
        ]);
        $formMapper->add('availability',CheckboxType::class,[
            'required'=>false,
            'label'=>'Disponible ?'
        ]);
        $formMapper->add('damage',CheckboxType::class,[
            'required'=>false,
            'label'=>'Dégradée ?'
        ]);

    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper->addIdentifier('reference',null,['route'=>['name'=>'show']]);
        $listMapper->add('city',null,['label'=>'Ville']);
        $listMapper->add('address',null,['label'=>'Adresse']);
        $listMapper->add('availability',null,['label'=>'Disponible ?']);
        $listMapper->add('damage',null,['label'=>'Dégradée ?']);
        $listMapper->add('capacityMax',null,['label'=>'Capacité maximum']);
        $listMapper->add('actualCapacity',null,['label'=>'Nombre de bouteilles actuel']);

    }

    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->with('Benne : ' . $showMapper->getAdmin()->getSubject()->getReference(), [
                'class'       => 'col-md-12',
                'box_class'   => 'box box-solid box-primary',
                'description' => 'Votre benne',
            ])
            ->add('address', null, ['label' => 'Adresse'])
            ->add('actualCapacity', null, ['label' => 'Capacité'])
            ->add('availability', null, ['label' => 'Disponible ?'])
            ->add('damage', null, ['label' => 'Dégradée ?'])
            ->end()
            ->end()
        ;
    }

    public function createQuery($context = 'list')
    {
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();
        $query = parent::createQuery($context);
        if($user->getCity() != null) {
            $query->andWhere(
                $query->expr()->eq($query->getRootAliases()[0] . '.city', ':my_param')
            );
            $query->setParameter('my_param', $user->getCity());
        }

        return $query;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('city',null,['label'=>'Ville']);
        $datagridMapper->add('address',null,['label'=>'Adresse']);
        $datagridMapper->add('reference');
        $datagridMapper->add('capacityMax',null,['label'=>'Capacité maximum']);
        $datagridMapper->add('actualCapacity',null,['label'=>'Nombre de bouteilles']);
    }

    public function toString($object)
    {
        return $object instanceof Trashs
            ? $object->getReference()
            : 'Trash';
    }

    public function prePersist($object)
    {
        parent::prePersist($object); // TODO: Change the autogenerated stub
        $city = $object->getCity();
        $address = $object->getAddress();

        $inseeUrl = 'https://geo.api.gouv.fr/communes?nom=' . $city . '&fields=departement';
        $file = file_get_contents($inseeUrl);
        $json = json_decode($file, true);
        $code = $json[0]['code'];

        $opts = array('http'=>array('header'=>"User-Agent: StevesCleverAddressScript 3.7.6\r\n"));
        $context = stream_context_create($opts);
        $url = 'https://nominatim.openstreetmap.org/search.php?q=' . rawurlencode($city) . '%20' . rawurlencode($address) . '&format=json';
        $file = file_get_contents($url, false, $context);
        $json = json_decode($file, true);
        $lat = $json[0]['lat'];
        $lon = $json[0]['lon'];
        $token = '4TozhmTJ9jLsEVJRLrWHXF50V0xqSvy8OgXCfVJ6p0mZrEY6Ts6iU5Sear5w3gHq';
        $urlElevation = 'https://api.jawg.io/elevations?locations=' . $lat . ',' . $lon . '&access-token=' . $token;
        $fileElevation = file_get_contents($urlElevation, false, $context);
        $jsonElevation = json_decode($fileElevation, true);
        $elevation = $jsonElevation[0]['elevation'];
        $object->setLatitude($lat);
        $object->setLongitude($lon);
        $object->setAltitude($elevation);
        $object->setinseeCode($code);
    }
    public function postPersist($object)
    {
        parent::postPersist($object); //
        $repo = new TrashRepository();
        $repo->addJsonObject($object->getCity(),$object->getAddress(),$object->getCapacityMax(),$object->getActualCapacity(),$object->getAvailability(),$object->getDamage(),$object->getZip());


    }

}