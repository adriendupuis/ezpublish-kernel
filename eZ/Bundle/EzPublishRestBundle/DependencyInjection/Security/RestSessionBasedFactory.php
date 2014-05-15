<?php
/**
 * File containing the SessionBasedFactory class.
 *
 * @copyright Copyright (C) 1999-2014 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Bundle\EzPublishRestBundle\DependencyInjection\Security;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class RestSessionBasedFactory extends FormLoginFactory
{
    public function __construct()
    {
        parent::__construct();
        unset( $this->options['check_path'] );
        $this->defaultSuccessHandlerOptions = array();
        $this->defaultFailureHandlerOptions = array();
    }

    protected function createListener( $container, $id, $config, $userProvider )
    {
        $listenerId = $this->getListenerId();
        $listener = new DefinitionDecorator( $listenerId );
        $listener->replaceArgument( 2, $id );

        /** @var \Symfony\Component\DependencyInjection\ContainerBuilder $container */
        $listenerId .= '.'.$id;
        $container->setDefinition( $listenerId, $listener );
        $container->setAlias( 'ezpublish_rest.session_authenticator', $listenerId );

        if ( $container->hasDefinition( 'security.logout_listener.' . $id ) )
        {
            // Copying logout handlers to REST session authenticator, to allow proper logout using it.
            $logoutListenerDef = $container->getDefinition( 'security.logout_listener.' . $id );
            $logoutListenerDef->addMethodCall(
                'addHandler',
                array( new Reference( 'ezpublish_rest.security.authentication.logout_handler' ) )
            );

            foreach ( $logoutListenerDef->getMethodCalls() as $callArray )
            {
                if ( $callArray[0] !== 'addHandler' )
                {
                    continue;
                }

                $listener->addMethodCall( 'addLogoutHandler', $callArray[1] );
            }
        }

        return $listenerId;
    }

    protected function getListenerId()
    {
        return 'ezpublish_rest.security.authentication.listener.session';
    }

    public function getPosition()
    {
        return 'http';
    }

    public function getKey()
    {
        return 'ezpublish_rest_session';
    }

    protected function createEntryPoint( $container, $id, $config, $defaultEntryPoint )
    {
        return $defaultEntryPoint;
    }
}
