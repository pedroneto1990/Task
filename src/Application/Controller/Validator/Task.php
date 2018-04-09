<?php
namespace Application\Controller\Validator;

class Task
{
    const MESSAGE_EMPTY_LIST = 'Wow. You have nothing else to do. Enjoy the rest of your day!';
    const MESSAGE_EMPTY_CONTENT = 'Bad move! Try removing the task instead of deleting its content.';
    const MESSAGE_INVALID_TYPE = 'The task type you provided is not supported. You can only use shopping or work.';
    const MESSAGE_REMOVE_NOT_FOUND = 'Good news! The task you were trying to delete didn\'t even exist.';
    const MESSAGE_UPDATE_NOT_FOUND = 'Are you a hacker or something? The task you were trying to edit doesn\'t exist.';

    const MESSAGE_INVALID_SORT_ORDER = 'Invalid Sort Order';
    const MESSAGE_INVALID_DONE = 'Invalid Done';
    const MESSAGE_GET_NOT_FOUND = 'Oops! Task not found!';
    const MESSAGE_INTERNAL_ERROR = 'Oops! Something is wrong!';
    const MESSAGE_BAD_REQUEST = 'Bad request!';

    /**
     * Task types
     * @var array
     */
    static protected $types = [
        'shopping',
        'work'
    ];

    /**
     * Validate request to create a task
     *
     * @param array $request
     * @return mixed (bool|string)
     */
    static public function toCreate(array $request)
    {
        if (!isset($request['content']) || empty($request['content'])) {
            return static::MESSAGE_EMPTY_CONTENT;
        }

        if (!isset($request['type']) || !in_array($request['type'], static::$types)) {
            return static::MESSAGE_INVALID_TYPE;
        }

        return true;
    }

    /**
     * Validate request to update a task
     *
     * @param array $request
     *
     * @return mixed (bool|string)
     */
    static public function toPut(array $request)
    {
        if (!isset($request['sort_order']) || !is_numeric($request['sort_order'])) {
            return static::MESSAGE_INVALID_SORT_ORDER;
        }

        if (!isset($request['done']) || !is_bool($request['done'])) {
            return static::MESSAGE_INVALID_DONE;
        }

        return static::toCreate($request);
    }
}