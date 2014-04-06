<?php
class Puravit_Quiz_Model_Resource_Eav_Mysql4_Setup extends Mage_Eav_Model_Entity_Setup
{
	public function getDefaultEntities()
    {
        return array(
            'customer'                       => array(
                'entity_model'                   => 'customer/customer',
                'attribute_model'                => 'customer/attribute',
                'table'                          => 'customer/entity',
                'increment_model'                => 'eav/entity_increment_numeric',
                'additional_attribute_table'     => 'customer/eav_attribute',
                'entity_attribute_collection'    => 'customer/attribute_collection',
                'attributes'                     => array(
                    'recommendation'         => array(
                        'type'               => 'varchar',
                        'label'              => 'Recommended Product',
                        'input'              => 'text',
                        'sort_order'         => 10,
                        'position'           => 10,

                    ),
 					'quiz_visitor'         => array(
                        'type'               => 'varchar',
                        'label'              => 'Recommended Product',
                        'input'              => 'text',
                        'sort_order'         => 10,
                        'position'           => 10,

                    ),
 					'size_top'         => array(
                        'type'               => 'varchar',
                        'label'              => 'Tops Size',
                        'input'              => 'text',
                        'sort_order'         => 10,
                        'position'           => 10,

                    ),
 					'size_bottom'         => array(
                        'type'               => 'varchar',
                        'label'              => 'Bottoms Size',
                        'input'              => 'text',
                        'sort_order'         => 10,
                        'position'           => 10,

                    )
               )
           )
      	);
    }
}