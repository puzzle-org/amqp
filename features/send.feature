Feature: sending AMQP messages

Background: Always test on an empty broker
    Given The queue 'test_1' is empty

Scenario: Send text message
    When I send the text message "Welcome to puzzle"
    Then The queue 'test_1' must contain 1 message
    And The message in queue 'test_1' contains "Welcome to puzzle" and is a text message

Scenario: Send json message
    When I send the json message '{"meat":"beef", "with":"fries"}'
    Then The queue 'test_1' must contain 1 message
    And The message in queue 'test_1' contains '{"meat":"beef","with":"fries"}' and is a json message
