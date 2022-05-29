<?php

namespace PHPSTORM_META {

    expectedArguments(
        \Hyqo\Http\Response::header(),
        0,
        \Hyqo\Http\Enum\Header::CONTENT_TYPE,
        \Hyqo\Http\Enum\Header::LOCATION,
    );
    expectedArguments(\Hyqo\Http\Response::setContentType(), 0, \Hyqo\Http\ContentType::JSON);
//    exitPoint(\Hyqo\HTTP\Response::send());
}
