Feature: Consuming AMQP messages

Scenario: Consuming a text message
    Given The queue 'test_1' contains the text message "Puzzle is great"
    When I consume all the messages in the queue 'test_1'
    Then I have consumed 1 message
    And the message is a text one 
    And the message contains "Puzzle is great"

Scenario: Consuming a json message
    Given The queue 'test_1' contains the json message '{"vendor":"puzzle", "name":"amqp"}'
    When I consume all the messages in the queue 'test_1'
    Then I have consumed 1 message
    And the message is a json one 
    And the message contains the json '{"vendor":"puzzle", "name":"amqp"}'

Scenario: Consuming many messages
    Given The queue 'test_1' contains the text message "Puzzle is great"
    And The queue 'test_1' contains the json message '{"lastName":"Poteau", "firstName":"Alexis"}'
    And The queue 'test_1' contains the text message "Puzzle is wonderful"
    When I consume all the messages in the queue 'test_1'
    Then I have consumed 3 messages
    And one of the messages is a text one 
    And one of the messages is a json one 
    And one of the messages contains "Puzzle is great"
    And one of the messages contains "Puzzle is wonderful"
    And one of the messages contains the json '{"lastName":"Poteau", "firstName":"Alexis"}'

Scenario: Consuming a compressed text message
    Given The queue 'test_zip' contains the compressed text message "Puzzle is great"
    When I consume all the messages in the queue 'test_zip'
    Then I have consumed 1 message
    And the message is an uncompressed text one
    And the message contains "Puzzle is great"
