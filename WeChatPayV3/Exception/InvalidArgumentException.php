<?php declare(strict_types=1);

namespace Plugin\WechatPayH5\WeChatPayV3\Exception;

use GuzzleHttp\Exception\GuzzleException;

class InvalidArgumentException extends \InvalidArgumentException implements WeChatPayException, GuzzleException
{
}
