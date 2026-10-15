@valid
Feature: ValidMessage
  In order to make an event available to a service
  I should be able to insert the event data into event database table

@unfiltered @untranslated
Scenario: Insert a valid message from an unfiltered and untranslated channel
  Given The channel is set
  When listener encounters a valid message
  Then it should insert it in db

@corellation_id
Scenario: Insert a valid message from an unfiltered and untranslated channel with correlation id
  Given The channel is set
  When listener encounters a valid message with correlation id
  Then it should insert it in db with correlation id

@user_id
Scenario: Insert a valid message from an unfiltered and untranslated channel with user id
  Given The channel is set
  When listener encounters a valid message with user id
  Then it should insert it in db with user id

@duplicate
Scenario: Skip a duplicate valid message from an unfiltered and untranslated channel
  Given The channel is set
  When listener encounters a valid message
  And listener encounters the same valid message
  Then it should insert it in db only once

@same-id
Scenario: Insert a second valid message with same id from another unfiltered and untranslated channel
  Given The channel is set
  When listener encounters a valid message
  And listener encounters a valid message with same id from different channel
  Then it should insert both in db

  @batch
  Scenario: Insert a burst of valid messages with a duplicate among them, in one batch
    Given The channel is set
    When listener encounters 50 valid messages at once, one of them twice
    Then it should insert 50 of them in db
