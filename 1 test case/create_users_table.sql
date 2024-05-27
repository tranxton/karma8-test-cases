CREATE TABLE public.users (
	id                        bigint      NOT NULL,
		CONSTRAINT users_pk
			PRIMARY KEY,
	username                  varchar(50) NOT NULL,
	email                     varchar(50) NOT NULL,
	subscription_active_until timestamp(0) DEFAULT NULL,
	email_confirmed_at        timestamp(0) DEFAULT NULL,
	email_validated_at        timestamp(0) DEFAULT NULL
);

CREATE INDEX users_active_subscriptions_with_valid_emails
	ON public.users(subscription_active_until, email_confirmed_at, email, email_validated_at) WHERE subscription_active_until IS NOT NULL AND email_confirmed_at IS NOT NULL;
