Feature: sending AMQP messages

Background: Always test on an empty broker
    Given The queue 'test_1' is empty

Scenario: Send text message
    When I send the text message "Welcome to puzzle"
    Then The queue 'test_1' must contain 1 message
    And The message in queue 'test_1' contains "Welcome to puzzle" and is a text message

Scenario: Send custom typed message
    When I send the xml message "<xml><message>Hello world</message></xml>"
    Then The queue 'test_1' must contain 1 message
    And The message in queue 'test_1' contains "<xml><message>Hello world</message></xml>" and is a xml message

Scenario: Send json message
    When I send the json message '{"meat":"beef", "with":"fries"}'
    Then The queue 'test_1' must contain 1 message
    And The message in queue 'test_1' contains '{"meat":"beef","with":"fries"}' and is a json message

Scenario: Send gzipped text message
    When I send the gzipped text message 'Compressed text'
    Then The queue 'test_1' must contain 1 message
    And The message in queue 'test_1' contains a gzipped message
