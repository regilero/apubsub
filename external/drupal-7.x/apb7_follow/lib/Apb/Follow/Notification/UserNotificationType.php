<?php

namespace Apb\Follow\Notification;

use Apb\Follow\Notification;
use Apb\Follow\NotificationTypeInterface;

class UserNotificationType implements NotificationTypeInterface
{
    /**
     * (non-PHPdoc)
     * @see \Apb\Follow\NotificationTypeInterface::getUri()
     */
    public function getUri(Notification $notification)
    {
        if (!($uid = $notification->getSourceId()) || !($account = user_load($uid))) {
            return null;
        }

        if (!$account->uid) {
            // Cannot give a URI for the anonymous user
            return null;
        }

        // Hope this method will check for access rights too
        if (($uri = entity_uri('user', $account)) && isset($uri['path'])) {
            return $uri['path'];
        } else {
            return null;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Follow\NotificationTypeInterface::format()
     */
    public function format(Notification $notification)
    {
        if (!($uid = $notification->getSourceId()) || !($account = user_load($uid))) {
            $account = drupal_anonymous_user();
        }

        // theme_username() is stupid and doesn't let us controler whether or
        // not we want the link over the username. Problem is that themes or
        // custom sites might utilize this method to output something else
        // than the $account->name property
        $accountTitle = theme('username', array(
            'account' => $account,
        ));

        $tVariables = array(
            '%username' => strip_tags($accountTitle),
        );

        switch ($notification->get('a')) {

            case 'login':
                return t("%username logged in!", $tVariables);

            case 'logout':
                return t("%username has left.", $tVariables);

            default:
                // Users can do all sort of things, so let's the user
                // notification act upon other types
                // FIXME: Do it (at least entity)

                return t("%username did something.", $tVariables);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \Apb\Follow\NotificationTypeInterface::getImageURI()
     */
    public function getImageURI(Notification $notification)
    {
        if (true /* @todo CONFIGURABRU !!! */ &&
            ($uid = $notification->getSourceId()) &&
            ($account = user_load($uid)) &&
            isset($account->picture) &&
            !empty($account->picture))
        {
            // WTF...
            list(, $path) = explode('://', $account->picture->uri, 2);

            return image_style_url('icon-32', $path);
        }

        // This is FALLBAAAAACK!
        switch ($notification->get('a')) {

            case 'login':
                return drupal_get_path('module', 'apb7_follow') . '/images/symbolic/comment-32.png';

            case 'logout':
                return drupal_get_path('module', 'apb7_follow') . '/images/symbolic/offline-32.png';

            default:
                return null;
        }
    }
}