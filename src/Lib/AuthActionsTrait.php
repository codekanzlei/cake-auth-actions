<?php
declare(strict_types = 1);

namespace AuthActions\Lib;

use Cake\Core\Configure;
use Cake\Event\EventManager;

trait AuthActionsTrait
{

    /**
     * @var AuthActions
     */
    protected $_AuthActions;

    /**
     * @var UserRights
     */
    protected $_UserRights;

    /**
     * Initializer, must be called in beforeFilter()
     *
     * @return void
     */
    public function initAuthActions()
    {
        EventManager::instance()->attach(function (\Cake\Event\Event $event) {
            // Make the AuthComponent, AuthActions and UserRights available to the view.
            // FIXME - find a clean way to do this
            if (!$event->subject() instanceof \Cake\Controller\ErrorController) {
                $viewAuthActions = [
                    'AuthActions' => $event->subject()->getAuthActions(),
                    'UserRights' => $event->subject()->getUserRights()
                ];
                $event->subject()->set('viewAuthActions', $viewAuthActions);
            }
        }, 'Controller.beforeRender');

        if ($this->getAuthActions()->isPublicAction($this->request->params)) {
            $this->Auth->allow();
        }
    }

    /**
     * Instance getter for the AuthActions instance
     *
     * @return AuthActions
     */
    public function getAuthActions(): AuthActions
    {
        if (!$this->_AuthActions) {
            if (Configure::load('auth_actions') === false) {
                trigger_error('AuthActions: Could not load config/auth_actions.php', E_USER_WARNING);
            }

            $actionConfig = Configure::read('auth_actions');
            $publicActionsConfig = Configure::read('public_actions');
            $options = Configure::read('auth_settings');
            if (!is_array($options)) {
                $options = [];
            }
            if (!is_array($publicActionsConfig)) {
                $publicActionsConfig = [];
            }

            $this->_AuthActions = new AuthActions($actionConfig, $publicActionsConfig, $options);
        }

        return $this->_AuthActions;
    }

    /**
     * Instance getter for the UserRights instance
     *
     * @return UserRights
     */
    public function getUserRights(): UserRights
    {
        if (!$this->_UserRights) {
            if (Configure::load('user_rights') === false) {
                trigger_error('UserRights: Could not load config/user_rights.php', E_USER_WARNING);
            }

            $rightsConfig = Configure::read('user_rights');
            if (!is_array($rightsConfig)) {
                $rightsConfig = [];
            }

            $this->_UserRights = new UserRights($rightsConfig);
        }

        return $this->_UserRights;
    }

    /**
     * See AuthActions::isAuthorized()
     *
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return $this->getAuthActions()->isAuthorized(
            $this->Auth->user(),
            $this->request->params
        );
    }

    /**
     * See UserRights::userHasRight()
     *
     * @param string $right right name
     * @return bool
     */
    public function hasRight(string $right): bool
    {
        $user = $this->Auth->user();
        if ($user !== null) {
            return $this->getUserRights()->userHasRight($user, $right);
        }

        return false;
    }
}
