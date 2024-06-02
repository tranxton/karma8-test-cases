CREATE TABLE users (
	id                        bigint      NOT NULL,
	CONSTRAINT users_pk
		PRIMARY KEY(id),
	username                  varchar(50) NOT NULL,
	email                     varchar(50) NOT NULL,
	subscription_active_until timestamp(0) DEFAULT NULL,
	email_confirmed_at        timestamp(0) DEFAULT NULL,
	email_validated_at        timestamp(0) DEFAULT NULL
);

CREATE INDEX users_active_subscriptions_with_valid_emails
	ON users(subscription_active_until, email_confirmed_at, email,
			 email_validated_at) WHERE subscription_active_until IS NOT NULL AND email_confirmed_at IS NOT NULL;

INSERT INTO users (id, username, email, subscription_active_until, email_confirmed_at, email_validated_at)
VALUES (1, 'testuser1', 'asd@example.com', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
	(2, 'testuser2', 'gfsa@example.com', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL),
	(3, 'testuser3', 'test3@example.com', CURRENT_TIMESTAMP, NULL, CURRENT_TIMESTAMP),
	(4, 'testuser4', 'fffeest4@example.com', NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
	(5, 'testuser5', 'famra@example.com', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
