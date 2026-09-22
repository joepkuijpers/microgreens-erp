#!/usr/bin/env python3
import sys
import RPi.GPIO as GPIO

pin = int(sys.argv[1])
state = int(sys.argv[2])

# Gebruik BCM nummering (zoals GPIO 17)
GPIO.setmode(GPIO.BCM)
GPIO.setwarnings(False)
GPIO.setup(pin, GPIO.OUT)
GPIO.output(pin, state)

print(f"GPIO {pin} gezet op {state}")

# We doen GEEN cleanup(), want dan wordt de pin weer vrijgegeven.
# Voor een simpele aan/uit actie is dat prima, maar als we snel schakelen
# is het beter de setup te behouden. Voor nu is dit veilig.
